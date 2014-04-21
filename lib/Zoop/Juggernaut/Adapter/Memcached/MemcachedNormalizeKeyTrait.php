<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\Memcached;

trait MemcachedNormalizeKeyTrait
{
    public function normalizeKey($key)
    {
        return str_replace(' ', '', mb_convert_encoding($key, 'ASCII'));
    }
}
