<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

interface CacheFloodProtectionInterface
{
    public function queue($key);

    public function clearQueue($key = null);

    public function reCache($key);

    public function isReCacheInProgress($key);

    public function isQueueInProgress($key);
}
