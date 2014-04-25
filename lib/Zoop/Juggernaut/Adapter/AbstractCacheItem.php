<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractCacheItem
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cachePool;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var boolean
     */
    protected $isHit;

    /**
     * @var \DateTime
     */
    protected $expiration;

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->isHit() ? $this->value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value = null, $ttl = null)
    {
        $this->value = $value;
        $this->setExpiration($ttl);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $return = $this->write($this->key, $this->value, $this->expiration);
        if($return === true) {
            $this->setIsHit(true);
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->getCachePool()->delete($this->key);
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        return $this->isHit;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCachePool()
    {
        return $this->cachePool;
    }

    /**
     * @param CacheItemPoolInterface $cachePool
     */
    public function setCachePool(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * Sets the expiration for this cache item.
     *
     * @param mixed $ttl
     *   The TTL to convert to a DateTime expiration.
     */
    protected function setExpiration($ttl)
    {
        if ($ttl instanceof \DateTime) {
            $this->expiration = $ttl;
        } elseif (is_int($ttl)) {
            $this->expiration = new \DateTime('now +' . $ttl . ' seconds');
        } elseif (is_null($this->expiration)) {
            $this->expiration = new \DateTime('now +1 year');
        }
    }

    /**
     * @param string $key
     */
    protected function setKey($key)
    {
        $this->key = (string) $key;
    }

    /**
     * @param mixed $value
     */
    protected function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @param boolean $isHit
     */
    protected function setIsHit($isHit)
    {
        $this->isHit = (boolean) $isHit;
    }

    /**
     * Commits this cache item to storage.
     *
     * @param $key
     *   The key of the cache item to save.
     * @param $value
     *   The value to save. It should not be serialized.
     * @param \DateTime $expiration
     *   The timestamp after which this cache item should be considered expired.
     * @return boolean
     *   Returns true if the item was successfully committed, or false if there was
     *   an error.
     */
    abstract protected function write($key, $value, \DateTime $expiration);
}
