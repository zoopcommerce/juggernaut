<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

use Zend\Serializer\Adapter\AdapterInterface;
use Zend\Serializer\Adapter\PhpSerialize;

trait CacheItemSerializerTrait
{
    protected $serializer;

    /**
     * @return AdapterInterface
     */
    public function getSerializer()
    {
        //set a default serializer if not set
        if (!isset($this->serializer)) {
            $this->setSerializer(new PhpSerialize);
        }
        return $this->serializer;
    }

    /**
     * @param AdapterInterface $serializer
     */
    public function setSerializer(AdapterInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Takes in data returning serialized data
     *
     * @param @mixed $data
     * @return string
     */
    public function serialize($data)
    {
        return $this->getSerializer()->serialize($data);
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
        return $this->getSerializer()->unserialize($data);
    }
}
