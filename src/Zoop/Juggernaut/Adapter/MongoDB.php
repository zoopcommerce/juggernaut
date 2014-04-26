<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

use \MongoCursorException;
use \MongoRegex;
use \MongoCollection;

class MongoDB extends AbstractAdapter implements AdapterInterface
{
    private $mongoCollection;

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

    public function setItem($key, $value)
    {
        //save the value to the local class cache
        parent::setItem($key, $value);

        $id = $key;
        $this->normalizeKey($id);

        $this->getMongoCollection()->update(
                array('_id' => $id), array(
            '_id' => $id,
            'ttl' => $this->ttl + time(),
            'value' => $this->encodeValue($value)
                ), array('upsert' => true)
        );

        $this->removeTempFiles($id);
    }

    public function getItem($key, &$success = null, $queue = true)
    {
        //check to see if it's already been cached in the class
        $value = parent::getItem($key, $success, $queue);

        if ($success === true) {
            return $value;
        } else {
            //create a key/id
            $id = $key;
            $this->normalizeKey($id);

            $cache = $this->getFromMongo($id);

            if ($queue === true && is_null($cache)) {
                if ($this->isQueueInProgress($id) === true) {
                    //anonymous function to test if we should continue to wait
                    $mongo = $this->getMongoCollection();
                    $condition = function() use ($mongo, $id) {
                        $cache = $mongo->findOne(array(
                            '$id' => $id
                        ));
                        return !is_null($cache);
                    };

                    $wait = $this->wait($condition);
                    if ($wait === false) {
                        $success = false;
                        return null;
                    } else {
                        $success = true;

                        $cache = $this->getFromMongo($id);
                        $value = $this->decodeValue($cache['value']);

                        return $value;
                    }
                } else {
                    $this->queue($id);
                    $success = false;
                    return null;
                }
            } else {
                if ($cache['ttl'] < time() && $this->isReCacheInProgress($id) === false) {
                    //set the queue
                    $this->reCache($id);
                    $success = false;
                    return null;
                } else {
                    $success = true;
                    $value = $this->decodeValue($cache['value']);

                    return $value;
                }
            }
        }
    }

    public function getFromMongo($id)
    {
        return $this->getMongoCollection()->findOne(array(
                    '_id' => $id
        ));
    }

    public function queue($key)
    {
        try {
            $this->getMongoCollection()->insert(array(
                '_id' => $this->getQueuedId($key),
                'ttl' => (time() + $this->queueTtl)
            ));
        } catch (MongoCursorException $e) {
            
        }
    }

    public function reCache($key)
    {
        try {
            $this->getMongoCollection()->insert(array(
                '_id' => $this->getReCacheId($key),
                'ttl' => (time() + $this->reCacheTtl)
            ));
        } catch (MongoCursorException $e) {
            
        }
    }

    public function isReCacheInProgress($key)
    {
        $key = $this->getReCacheId($key);

        $reCache = $this->getMongoCollection()->findOne(array(
            '$id' => $key
        ));

        if (!is_null($reCache)) {
            if ($reCache['ttl'] > time()) {
                return true;
            } else {
                $this->getMongoCollection()->remove(array('$id' => $key));
            }
        }
        return false;
    }

    public function isQueueInProgress($key)
    {
        $key = $this->getQueuedId($key);

        $reCache = $this->getMongoCollection()->findOne(array(
            '$id' => $key
        ));

        if (!is_null($reCache)) {
            if ($reCache['ttl'] > time()) {
                return true;
            } else {
                $this->getMongoCollection()->remove(array('$id' => $key));
            }
        }
        return false;
    }

    private function removeTempFiles($id)
    {
        $qId = $this->getQueuedId($id);
        $rId = $this->getReCacheId($id);

        $this->getMongoCollection()->remove(array('_id' => $qId));
        $this->getMongoCollection()->remove(array('_id' => $rId));
    }

    public function clearQueue($key = null)
    {
        if (is_null($key)) {
            $this->getMongoCollection()->remove(array('_id' => new MongoRegex('/.*\.' . self::QUEUING_ID . '/')));
            $this->getMongoCollection()->remove(array('_id' => new MongoRegex('/.*\.' . self::RECACHE_ID . '/')));
        } else {
            $id = $key;
            $this->normalizeKey($id);
            $this->removeTempFiles($id);
        }
    }
}
