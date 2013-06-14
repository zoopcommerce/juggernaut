<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Adapters;

class Memcached extends AbstractAdapter implements AdapterInterface {

    public function __construct() {
        
    }

    public function setItem($name, $value, $ttl = 600, $force = false) {
        
    }

    public function getItem($name, $queue = false) {
        
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