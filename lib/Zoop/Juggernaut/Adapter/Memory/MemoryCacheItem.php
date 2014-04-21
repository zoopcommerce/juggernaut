<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\Memory;

use \DateTime;
use Psr\Cache\CacheItemInterface;
use Zoop\Juggernaut\Adapter\AbstractCacheItem;
use Zoop\Juggernaut\Adapter\Memory\MemoryCachePool;

class MemoryCacheItem extends AbstractCacheItem implements CacheItemInterface
{
    /**
     * @param MemoryCachePool $cachePool
     * @param string $key
     * @param mixed $value
     * @param boolean $hit
     * @param DateTime $expiration
     */
    public function __construct(
        MemoryCachePool $cachePool,
        $key,
        $value = null,
        $hit = false,
        DateTime $expiration = null
    ) {
        $this->setCachePool($cachePool);
        $this->setExpiration($expiration);
        $this->setKey($key);
        $this->setValue($value);
        $this->setIsHit($hit);
    }

    /**
     * {@inheritdoc}
     */
    protected function write($key, $value, DateTime $ttd)
    {
        return $this->getCachePool()->write($key, $value, $ttd);
    }
}
