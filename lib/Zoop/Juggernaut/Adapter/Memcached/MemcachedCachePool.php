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

class MemcachedCachePool extends AbstractCachePool implements CacheItemPoolInterface, NormalizedKeyInterface
{
    use CacheItemSerializerTrait;
    use MemcachedNormalizeKeyTrait;

    protected $memcached;

    public function __construct(Memcached $memcached)
    {
        $this->setMemcached($memcached);
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
         return $this->getMemcached()
            ->set(
                $this->normalizeKey($key),
                $value,
                $expiration->getTimestamp()
            );
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
        $value = $this->getMemcached()
            ->get($this->normalizeKey($key));

        if ($value !== false) {
            return new MemcachedCacheItem($this, $key, $value, true);
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
}
