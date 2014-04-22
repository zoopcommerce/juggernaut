<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

trait CacheItemSerializerTrait
{
    /**
     * Takes in data returning serialized data
     *
     * @param @mixed $data
     * @return string
     */
    public function serialize($data)
    {
        return serialize($data);
    }

    /**
     * Takes a serialized string and returns the
     * original type
     *
     * @param string $data
     * @return @mixed
     */
    public function unserialize($data)
    {
        return unserialize($data);
    }
}
