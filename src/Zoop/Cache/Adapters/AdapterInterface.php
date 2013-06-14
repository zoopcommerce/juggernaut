<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Adapters;

interface AdapterInterface {

    public function setItem($key, $value);

    public function getItem($key, &$success = null, $queue = false);

    public function queue($id);

    public function reCache($id);

    public function isReCacheInProgress($id);

    public function isQueueInProgress($id);

    public function normalizeKey(&$key);

    public function setTtl($ttl);
}