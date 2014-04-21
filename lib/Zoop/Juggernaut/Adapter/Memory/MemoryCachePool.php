<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\Memory;

use \DateTime;
use Psr\Cache\CacheItemPoolInterface;
use Zoop\Juggernaut\Adapter\AbstractCachePool;
use Zoop\Juggernaut\Adapter\CacheException;
use Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem;

class MemoryCachePool extends AbstractCachePool implements CacheItemPoolInterface
{
    /**
     * The stored data in this cache pool.
     *
     * @var array
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if (!array_key_exists($key, $this->data) || $this->data[$key]['expiration'] < new DateTime()) {
            return new MemoryCacheItem($this, $key);
        }

        return new MemoryCacheItem($this, $key, $this->data[$key]['value'], true, $this->data[$key]['expiration']);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->data = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->data[$key]);
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
        $this->data[$key] = [
            'value' => $value,
            'expiration' => $expiration,
            'hit' => true,
        ];
        return true;
    }
}
