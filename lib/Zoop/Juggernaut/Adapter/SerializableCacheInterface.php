<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

use Zend\Serializer\Adapter\AdapterInterface;

interface SerializableCacheInterface
{
    public function getSerializer();

    public function setSerializer(AdapterInterface $serializer);

    public function serialize($data);

    public function unserialize($data);
}
