<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\Chained;

use \DateTime;
use Psr\Cache\CacheItemInterface;
use Zoop\Juggernaut\Adapter\AbstractCacheItem;
use Zoop\Juggernaut\Adapter\Chained\ChainedCachePool;

class ChainedCacheItem extends AbstractCacheItem implements CacheItemInterface
{
    protected $class;

    /**
     *
     * @param ChainedCachePool $cachePool
     * @param string $key
     * @param mixed $value
     * @param boolean $hit
     * @param DateTime $expiration
     * @param string $class
     */
    public function __construct(
        ChainedCachePool $cachePool,
        $key,
        $value = null,
        $hit = false,
        DateTime $expiration = null,
        $class = null
    ) {
        $this->setCachePool($cachePool);
        $this->setExpiration($expiration);
        $this->setKey($key);
        $this->setValue($value);
        $this->setIsHit($hit);
        $this->setClass($class);
    }

    /**
     * {@inheritdoc}
     */
    protected function write($key, $value, DateTime $ttd)
    {
        return $this->getCachePool()->write($key, $value, $ttd);
    }

    /**
     * Gets the class name of the cached item
     *
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets the class name of the cached item
     *
     * @param string|null $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }
}
