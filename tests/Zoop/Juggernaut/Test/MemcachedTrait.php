<?php

namespace Zoop\Juggernaut\Test;

use \Memcached;

trait MemcachedTrait
{
    protected static $MEMCACHED_HOST = 'localhost';
    protected static $MEMCACHED_PORT = 11211;

    protected $memcached;

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
}
