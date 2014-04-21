<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\MongoDb;

use \DateTime;
use \MongoCollection;
use \MongoCursor;
use \Exception;
use Psr\Cache\CacheItemPoolInterface;
use Zoop\Juggernaut\Adapter\AbstractCachePool;
use Zoop\Juggernaut\Adapter\CacheException;
use Zoop\Juggernaut\Adapter\CacheItemSerializerTrait;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem;
use Zoop\Juggernaut\Adapter\SerializableCacheInterface;

class MongoDbCachePool extends AbstractCachePool implements CacheItemPoolInterface, SerializableCacheInterface
{
    use CacheItemSerializerTrait;

    protected $mongoCollection;

    /**
     * @param MongoCollection $mongoCollection
     */
    public function __construct(MongoCollection $mongoCollection)
    {
        $this->setMongoCollection($mongoCollection);
    }

    /**
     * @return MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->mongoCollection;
    }

    /**
     * @param MongoCollection $mongoCollection
     */
    public function setMongoCollection(MongoCollection $mongoCollection)
    {
        $this->mongoCollection = $mongoCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        if (($cacheItem = $this->find($key)) === false) {
            return new MongoDbCacheItem($this, $key);
        }

        return $cacheItem;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $r = $this->getMongoCollection()
            ->remove([], ['justOne' => false]);

        if (!$r['ok']) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $r = $this->getMongoCollection()
            ->remove(
                [
                    '_id' => [
                        '$in' => $keys
                    ]
                ],
                [
                    'justOne' => false
                ]
            );

        if (!$r['ok']) {
            return false;
        }
        return true;
    }

    /**
     * @param $key
     * @param mixed $value
     *   The
     * @param DateTime $expiration
     *   The time after which the saved item should be considered expired.
     */
    public function write($key, $value, DateTime $expiration)
    {
        $r = $this->getMongoCollection()
            ->update(
                [
                    '_id' => $key
                ],
                [
                    'value' => $this->serialize($value),
                    'ttd' => $expiration
                ],
                [
                    'upsert' => true,
                ]
            );

        if (!$r['ok']) {
            return false;
        }
        return true;
    }

    /**
     * Remove cache item from MongoDb
     *
     * @param type $key
     * @return boolean
     * @throws Exception
     */
    public function delete($key)
    {
        $r = $this->getMongoCollection()
            ->remove(['_id' => $key], ['justOne' => true]);

        if (!$r['ok']) {
            return false;
        }
        return true;
    }

    /**
     * Queries Mongodb for the cache item
     *
     * @param string $key
     * @return boolean|MongoDbCacheItem
     */
    protected function find($key)
    {
        /* @var $cursor MongoCursor */
        $cursor = $this->getMongoCollection()
            ->find([
                '_id' => $key,
                'ttd' => [
                    '$gt' => new DateTime()
                ]
            ])
            ->limit(1);

        if ($cursor->count() === 1) {
            $data = iterator_to_array($cursor);
            return new MongoDbCacheItem($this, $key, $this->unserialize($data[$key]['value']), true);
        }

        return false;
    }
}
