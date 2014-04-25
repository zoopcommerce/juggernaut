<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\Memcached;

use \DateTime;
use \Memcached;
use \Exception;
use Psr\Cache\CacheItemPoolInterface;
use Zoop\Juggernaut\Adapter\AbstractCachePool;
use Zoop\Juggernaut\Adapter\CacheException;
use Zoop\Juggernaut\Adapter\CacheItemSerializerTrait;
use Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem;
use Zoop\Juggernaut\Adapter\Memcached\MemcachedNormalizeKeyTrait;
use Zoop\Juggernaut\Adapter\NormalizedKeyInterface;
use Zoop\Juggernaut\Adapter\FloodProtectionInterface;
use Zoop\Juggernaut\Adapter\FloodProtectionTrait;

class MemcachedCachePool extends AbstractCachePool implements
    CacheItemPoolInterface,
    NormalizedKeyInterface,
    FloodProtectionInterface
{
    use CacheItemSerializerTrait;
    use MemcachedNormalizeKeyTrait;
    use FloodProtectionTrait;

    protected $memcached;

    public function __construct(Memcached $memcached, $floodProtection = true)
    {
        $this->setMemcached($memcached);
        $this->setFloodProtection($floodProtection);
    }

    /**
     * @return Memcached
     */
    public function getMemcached()
    {
        return $this->memcached;
    }

    /**
     * @param Memcached $memcached
     */
    public function setMemcached(Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if (($cacheItem = $this->find($key)) === false) {
            return new MemcachedCacheItem($this, $key);
        }

        return $cacheItem;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->getMemcached()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->getMemcached()->deleteMulti($this->getNormalizedKeys($keys));
    }

    /**
     * Creates or updates a cache item on the memcached server
     *
     * @param $key
     * @param mixed $value
     *   The
     * @param DateTime $expiration
     *   The time after which the saved item should be considered expired.
     */
    public function write($key, $value, DateTime $expiration)
    {
        $result = $this->getMemcached()
            ->set(
                $this->normalizeKey($key),
                $value,
                $expiration->getTimestamp()
            );
        
        if($this->hasFloodProtection() === true) {
            $this->clearQueue($key);
        }
        
        return $result;
    }

    /**
     * Deletes a particular cache item on the memcached server
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        return $this->getMemcached()->delete($this->normalizeKey($key));
    }

    /**
     * Queries the memcached server to find the cache item
     *
     * @param string $key
     * @return MemcachedCacheItem|boolean
     */
    protected function find($key)
    {
        $find = function() use ($key) {
            $data = $this->getMemcached()
                ->get($this->normalizeKey($key));
            
            if (!isset($data)) {
                return false;
            }
            return $data;
        };

        $data = $find();
        if ($data !== false) {
            return new MemcachedCacheItem($this, $key, $data, true);
        } elseif ($this->hasFloodProtection() === true) {
            if ($this->isQueued($key) === true) {
                //wait and retry
                $data = $this->wait($find);
                if($data !== false) {
                    return new MongoDbCacheItem($this, $key, $data, true);
                }
            } else {
                //start the queuing process
                $this->queue($key);
            }
        }
        return false;
    }

    /**
     * Normalizes all keys within the array of passed keys
     *
     * @param array $keys
     * @return array
     */
    protected function getNormalizedKeys(array $keys)
    {
        $normalized = [];
        foreach ($keys as $key) {
            $normalized[] = $this->normalizeKey($key);
        }
        return $normalized;
    }
    
    /**
     * Queues the current cache key
     * 
     * @param string $key
     */
    public function queue($key)
    {
        try {
            $expiration = new DateTime('+' . $this->getQueueTtl() . 's');
            
            $this->getMemcached()
                ->set(
                    $this->getQueueKey($this->normalizeKey($key)),
                    '',
                    $expiration->getTimestamp()
                );
        } catch (MongoCursorException $e) {
            //nothing to see here. We already have the document inserted.
        }
    }

    /**
     * Clears the queueing entries
     * 
     * @param string|null $key
     */
    public function clearQueue($key = null)
    {
        if(!empty($key)) {
            $this->getMemcached()->deleteMulti([
                $this->getReCacheKey($this->normalizeKey($key)),
                $this->getQueueKey($this->normalizeKey($key))
            ]);
        } else {
            //this is expensive so avoid doing it if possible
            $deleteKeys = [];
            $allKeys = $this->getMemcached()->getAllKeys();
            
            foreach ($allKeys as $key) {
                if (preg_match('/.*\.(' . self::$QUEUED . '|' . self::$RECACHE . ')/', $key)) {
                    $deleteKeys[] = $key;
                }
            }
            
            if(!empty($deleteKeys)) {
                $this->getMemcached()->deleteMulti($deleteKeys); 
            }
        }
    }
    
    /**
     * Enters the recache entry
     * 
     * @param string $key
     */
    public function reCache($key)
    {
        try {
            $expiration = new DateTime('+' . $this->getReCacheTtl() . 's');
            
            $this->getMemcached()
                ->set(
                    $this->getReCacheKey($this->normalizeKey($key)),
                    '',
                    $expiration->getTimestamp()
                );
        } catch (MongoCursorException $e) {
            //nothing to see here. We already have the document inserted.
        }
    }

    /**
     * Checks if we are recached
     * 
     * @param string $key
     * @return boolean
     */
    public function isReCaching($key)
    {
        $value = $this->getMemcached()
            ->get($this->getReCacheKey($this->normalizeKey($key)));
        if(!empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if we are queued
     * 
     * @param string $key
     * @return boolean
     */
    public function isQueued($key)
    {
        $value = $this->getMemcached()
            ->get($this->getQueueKey($this->normalizeKey($key)));
        if(!empty($value)) {
            return true;
        }
        return false;
    }
}
