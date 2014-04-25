<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\File;

use \DateTime;
use Psr\Cache\CacheItemInterface;
use Zoop\Juggernaut\Adapter\AbstractCacheItem;
use Zoop\Juggernaut\Adapter\File\FileCachePool;

class FileCacheItem extends AbstractCacheItem implements CacheItemInterface
{
    /**
     *
     * @param FileCachePool $cachePool
     * @param string $key
     * @param mixed $value
     * @param boolean $hit
     * @param DateTime $expiration
     */
    public function __construct(
        FileCachePool $cachePool,
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
