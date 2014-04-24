<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter\MongoDb;

use \DateTime;
use \Exception;
use \MongoCollection;
use \MongoCursor;
use \MongoCursorException;
use \MongoRegex;
use Psr\Cache\CacheItemPoolInterface;
use Zoop\Juggernaut\Adapter\AbstractCachePool;
use Zoop\Juggernaut\Adapter\CacheException;
use Zoop\Juggernaut\Adapter\CacheItemSerializerTrait;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem;
use Zoop\Juggernaut\Adapter\SerializableCacheInterface;
use Zoop\Juggernaut\Adapter\FloodProtectionInterface;
use Zoop\Juggernaut\Adapter\FloodProtectionTrait;

class MongoDbCachePool extends AbstractCachePool implements
    CacheItemPoolInterface,
    SerializableCacheInterface,
    FloodProtectionInterface
{
    use CacheItemSerializerTrait;
    use FloodProtectionTrait;

    protected $mongoCollection;

    /**
     * @param MongoCollection $mongoCollection
     */
    public function __construct(MongoCollection $mongoCollection, $floodProtection = true)
    {
        $this->setMongoCollection($mongoCollection);
        $this->setFloodProtection($floodProtection);
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
        $result = $this->getMongoCollection()
            ->remove([], ['justOne' => false]);

        if (!$result['ok']) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $result = $this->getMongoCollection()
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

        if (!$result['ok']) {
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
        $result = $this->getMongoCollection()
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

        if (!$result['ok']) {
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
        $result = $this->getMongoCollection()
            ->remove(['_id' => $key], ['justOne' => true]);

        if (!$result['ok']) {
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
        $data = $this->getMongoCollection()
            ->findOne([
                '_id' => $key
            ]);

        if (isset($data)) {
            $cacheTtd = new DateTime($data['ttd']['date']);
            $now = new DateTime();

            if ($cacheTtd > $now || ($this->hasFloodProtection() === true && $this->isReCaching($key) === true)) {
                return new MongoDbCacheItem($this, $key, $this->unserialize($data['value']), true);
            } else {
                //start recaching process
                $this->reCache($key);
            }
        } elseif ($this->hasFloodProtection() === true) {
            if ($this->isQueued($key) === true) {
                //wait and retry
            } else {
                //start the queuing process
                $this->queue($key);
            }
        }

        return false;
    }
    
    public function queue($key)
    {
        try {
            $this->getMongoCollection()
                ->insert([
                    '_id' => $this->getQueueKey($key),
                    'ttd' => new DateTime('+' . $this->getQueueTtl() . 's')
                ]);
        } catch (MongoCursorException $e) {
            //nothing to see here. We already have the document inserted.
        }
    }

    public function clearQueue($key = null)
    {
        $queued = is_null($key) ? new MongoRegex('/.*\.' . self::$QUEUED . '/') : $this->getQueueKey($key);
        $recache = is_null($key) ? new MongoRegex('/.*\.' . self::$RECACHE . '/') : $this->getReCacheKey($key);
        
        $this->getMongoCollection()
            ->remove(['_id' => $queued]);
        $this->getMongoCollection()
            ->remove(['_id' => $recache]);
    }

    public function reCache($key)
    {
        try {
            $this->getMongoCollection()
                ->insert([
                    '_id' => $this->getReCacheKey($key),
                    'ttd' => new DateTime('+' . $this->getReCacheTtl() . 's')
                ]);
        } catch (MongoCursorException $e) {
            //nothing to see here. We already have the document inserted.
        }
    }

    public function isReCaching($key)
    {
        
    }

    public function isQueued($key)
    {
        $cursor = $this->getMongoCollection()
            ->findOne([
                '_id' => $this->getQueueKey($key)
            ]);
        
    }
}
