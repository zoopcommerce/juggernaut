<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\MongoDb;

use \DateTime;
use Psr\Cache\CacheItemInterface;
use Zoop\Juggernaut\Adapter\AbstractCacheItem;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool;

class MongoDbCacheItem extends AbstractCacheItem implements CacheItemInterface
{
    /**
     *
     * @param MongoDbCachePool $cachePool
     * @param string $key
     * @param mixed $value
     * @param boolean $hit
     * @param DateTime $expiration
     */
    public function __construct(
        MongoDbCachePool $cachePool,
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
