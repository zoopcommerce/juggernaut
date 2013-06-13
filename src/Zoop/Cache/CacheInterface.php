<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache;

interface CacheInterface {

    public function set($name, $value, $ttl = 600);

    public function get($name, $queue = false);

    public function queue($id);

    public function reCache($id);

    public function isReCacheInProgress($id);

    public function isQueueInProgress($id);
}