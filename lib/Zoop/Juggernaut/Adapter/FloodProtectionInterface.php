<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

interface FloodProtectionInterface
{
    public function queue($key);

    public function clearQueue($key = null);

    public function reCache($key);

    public function isReCaching($key);

    public function isQueued($key);
}
