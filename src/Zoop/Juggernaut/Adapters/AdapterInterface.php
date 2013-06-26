<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapters;

interface AdapterInterface {

    public function setItem($key, $value);

    public function getItem($key, &$success = null, $queue = false);

    public function queue($key);
    
    public function clearQueue($key = null);

    public function reCache($key);

    public function isReCacheInProgress($key);

    public function isQueueInProgress($key);

    public function normalizeKey(&$key);

    public function setTtl($ttl);
}