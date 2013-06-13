<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache;

class Memcached extends AbstractCache implements CacheInterface {

    public function __construct() {
        
    }

    public function set($name, $value, $ttl = 600, $force = false) {
        
    }

    public function get($name, $queue = false) {
        
    }

    public function queue($id) {
        
    }

    public function reCache($id) {
        
    }

    public function isReCacheInProgress($id) {
        
    }

    public function isQueueInProgress($id) {
        
    }

}