<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

interface SerializableCacheInterface
{
    public function serialize($data);

    public function unserialize($data);
}
