<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\Chained;

use \DateTime;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Zoop\Juggernaut\Adapter\AbstractCachePool;
use Zoop\Juggernaut\Adapter\CacheException;
use Zoop\Juggernaut\Adapter\Chained\ChainedCacheItem;

class ChainedCachePool extends AbstractCachePool implements CacheItemPoolInterface
{
    protected $cachePools = [];
    protected $cascadeDelete = true;
    protected $cascadeWrite = true;

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if (($cacheItem = $this->getItemFromCachePools($key)) === false) {
            return new ChainedCacheItem($this, $key);
        }

        return $cacheItem;
    }

    /**
     *
     * @param type $key
     * @return CacheItemInterface|boolean
     * @throws Exception
     */
    protected function getItemFromCachePools($key)
    {
        /* @var $cachePool CacheItemPoolInterface */
        foreach ($this->getCachePools() as $cachePool) {
            $cacheItem = $cachePool->getItem($key);
            if ($cacheItem->isHit() === true) {
                return new ChainedCacheItem(
                    $this,
                    $key,
                    $cacheItem->get(),
                    true,
                    null,
                    get_class($cacheItem)
                );
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->clearCachePools();
    }

    protected function clearCachePools()
    {
        $success = true;

        /* @var $cachePool CacheItemPoolInterface */
        foreach ($this->getCachePools() as $cachePool) {
            if ($cachePool->clear() === false) {
                $success = false;
            }

            if ($this->hasCascadeDelete() === false) {
                break;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->deleteFromCachePools($key);
    }

    protected function deleteFromCachePools($key)
    {
        $success = true;

        /* @var $cachePool CacheItemPoolInterface */
        foreach ($this->getCachePools() as $cachePool) {
            if ($cachePool->delete($key) === false) {
                $success = false;
            }

            if ($this->hasCascadeWrite() === false) {
                break;
            }
        }

        return $success;
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
        return $this->writeItemToCachePools($key, $value, $expiration);
    }

    /**
     * Iterates through the cache pools and attempts to write to them
     *
     * @param string $key
     * @param mixed $value
     * @param DateTime $expiration
     * @return boolean
     * @throws Exception
     */
    protected function writeItemToCachePools($key, $value, DateTime $expiration)
    {
        $success = true;

        /* @var $cachePool CacheItemPoolInterface */
        foreach ($this->getCachePools() as $cachePool) {
            if ($cachePool->write($key, $value, $expiration) === false) {
                $success = false;
            }

            if ($this->hasCascadeWrite() === false) {
                break;
            }
        }

        return $success;
    }

    /**
     * Add a cache pool to the chained pools.
     * Prevents duplicate pool instances.
     *
     * @param CacheItemPoolInterface $cachePool
     */
    public function addCachePool(CacheItemPoolInterface $cachePool)
    {
        if ($this->inCachePools($cachePool) === false) {
            $this->cachePools[] = $cachePool;
        }
    }

    /**
     * @param CacheItemPoolInterface $newCachePool
     */
    public function inCachePools(CacheItemPoolInterface $newCachePool)
    {
        foreach ($this->cachePools as $cachePool) {
            if ($newCachePool === $cachePool) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function hasCascadeDelete()
    {
        return $this->cascadeDelete;
    }

    /**
     * @return boolean
     */
    public function hasCascadeWrite()
    {
        return $this->cascadeWrite;
    }

    /**
     * @param boolean $cascadeDelete
     */
    public function setCascadeDelete($cascadeDelete)
    {
        $this->cascadeDelete = (boolean) $cascadeDelete;
    }

    /**
     * @param boolean $cascadeWrite
     */
    public function setCascadeWrite($cascadeWrite)
    {
        $this->cascadeWrite = (boolean) $cascadeWrite;
    }

    /**
     * @return array
     */
    public function getCachePools()
    {
        if (empty($this->cachePools)) {
            throw new Exception('There are no Cache Pools set. Please add at least one Cache Pool.');
        }
        return $this->cachePools;
    }

    /**
     * @param array $cachePools
     */
    public function setCachePools(array $cachePools)
    {
        $this->cachePools = $cachePools;
    }
}
