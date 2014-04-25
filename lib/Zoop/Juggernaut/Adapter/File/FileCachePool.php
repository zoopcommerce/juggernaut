<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\File;

use \DateTime;
use \Exception;
use Psr\Cache\CacheItemPoolInterface;
use Zoop\Juggernaut\Adapter\AbstractCachePool;
use Zoop\Juggernaut\Adapter\CacheException;
use Zoop\Juggernaut\Adapter\CacheItemSerializerTrait;
use Zoop\Juggernaut\Adapter\File\FileCacheItem;
use Zoop\Juggernaut\Adapter\SerializableCacheInterface;
use Zoop\Juggernaut\Adapter\FloodProtectionInterface;
use Zoop\Juggernaut\Adapter\FloodProtectionTrait;

class FileCachePool extends AbstractCachePool implements
    CacheItemPoolInterface,
    SerializableCacheInterface,
    FloodProtectionInterface
{

    use CacheItemSerializerTrait;
    use FloodProtectionTrait;

    protected $directory;

    /**
     * @param MongoCollection $mongoCollection
     */
    public function __construct($directory, $floodProtection = true)
    {
        $this->setDirectory($directory);
        $this->setFloodProtection($floodProtection);
    }

    /**
     * 
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if (($cacheItem = $this->find($key)) === false) {
            return new FileCacheItem($this, $key);
        }

        return $cacheItem;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $pattern = $this->getDirectory() . '/*';
        foreach (glob($pattern) as $file) {
            $this->deleteFile($file);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $result = [];

        if ($result) {
            return false;
        }
        return true;
    }

    /**
     * @param $key
     * @param mixed $value
     *   The
     * @param DateTime $expiration
     *   The time after which the saved item should be considered expired.
     */
    public function write($key, $value, DateTime $expiration)
    {
        $file = $this->getDirectory() . '/' . $key;
        $data = [
            'value' => $value,
            'ttd' => $expiration->getTimestamp()
        ];

        $result = file_put_contents($file, $this->serialize($data));

        if ($this->hasFloodProtection() === true) {
            $this->clearQueue($key);
        }

        return $result === false;
    }

    /**
     * Remove cache item from MongoDb
     *
     * @param type $key
     * @return boolean
     * @throws Exception
     */
    public function delete($key)
    {
        return $this->deleteFile($this->getDirectory() . '/' . $key);
    }

    /**
     * Queries Mongodb for the cache item
     *
     * @param string $key
     * @return boolean|FileCacheItem
     */
    protected function find($key)
    {
        /* @var $cursor MongoCursor */
        $find = function() use ($key) {
            $file = $this->getDirectory() . '/' . $key;
            if (is_file($file)) {
                return $this->unserialize(file_get_contents($file));
            }
            return false;
        };

        $data = $find();
        if ($data !== false) {
            $cacheTtd = new DateTime();
            $cacheTtd->setTimestamp(intval($data['ttd']));
            
            $now = new DateTime();

            if ($cacheTtd > $now || ($this->hasFloodProtection() === true && $this->isReCaching($key) === true)) {
                return new FileCacheItem($this, $key, $data['value'], true);
            } else {
                //start recaching process
                $this->reCache($key);
            }
        } elseif ($this->hasFloodProtection() === true) {
            if ($this->isQueued($key) === true) {
                //wait and retry
                $data = $this->wait($find);
                if ($data !== false) {
                    return new FileCacheItem($this, $key, $data['value'], true);
                }
            } else {
                //start the queuing process
                $this->queue($key);
            }
        }

        return false;
    }

    /**
     * Queues the current cache key
     * 
     * @param string $key
     */
    public function queue($key)
    {
        $file = $this->getDirectory() . '/' . $this->getQueueKey($key);
        if (!is_file($file)) {
            $ttd = new DateTime('+' . $this->getQueueTtl() . 's');
            file_put_contents($file, $ttd);
        }
    }

    /**
     * Clears the queueing entries
     * 
     * @param string|null $key
     */
    public function clearQueue($key = null)
    {
        if (is_null($key)) {
            $pattern = $this->getDirectory() . '/*.{' . self::$QUEUED . ',' . self::$RECACHE . '}';
            foreach (glob($pattern, GLOB_BRACE) as $file) {
                $this->deleteFile($file);
            }
        } else {
            $this->deleteFile($this->getQueueKey($key));
            $this->deleteFile($this->getReCacheKey($key));
        }
    }

    /**
     * Enters the recache entry
     * 
     * @param string $key
     */
    public function reCache($key)
    {
        $file = $this->getDirectory() . '/' . $this->getReCacheKey($key);
        if (!is_file($file)) {
            $ttd = new DateTime('+' . $this->getReCacheTtl() . 's');
            file_put_contents($file, $ttd);
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
        $reCacheFile = $this->getDirectory() . '/' . $this->getReCacheKey($key);
        if (is_file($reCacheFile)) {
            $ttl = file_get_contents($reCacheFile);
            if ($ttl > time()) {
                return true;
            } else {
                $this->deleteFile($reCacheFile);
            }
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
        $queueFile = $this->getDirectory() . '/' . $this->getQueueKey($key);
        if (is_file($queueFile)) {
            $ttl = file_get_contents($queueFile);
            if ($ttl > time()) {
                return true;
            } else {
                $this->deleteFile($queueFile);
            }
        }
        return false;
    }

    protected function deleteFile($file)
    {
        $result = false;
        if (is_file($file)) {
            $result = unlink($file);
        }
        return $result;
    }

}
