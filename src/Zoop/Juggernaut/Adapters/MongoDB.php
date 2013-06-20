<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapters;

use \MongoClient;
use \MongoCursorException;
use \MongoId;

class MongoDB extends AbstractAdapter implements AdapterInterface {

    private $mongo;
    private $database;
    private $collection = 'Cache';
    private $username = '';
    private $password = '';
    private $server = 'localhost';
    private $port = '27017';

    public function __construct($database, $username = '', $password = '', $server = 'localhost', $port = '27017') {
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->server = $server;
        $this->port = $port;
    }

    public function setItem($key, $value) {
        //save the value to the local class cache
        parent::setItem($key, $value);

        $id = $key;
        $this->normalizeKey($id);

        $this->mongo()->update(
                array('_id' => $id), array(
            '_id' => $id,
            'ttl' => $this->ttl + time(),
            'value' => $this->encodeValue($value)
                ), array('upsert' => true)
        );

        $this->removeTempFiles($id);
    }

    public function getItem($key, &$success = null, $queue = true) {
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
                    $mongo = $this->mongo();
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

    public function getFromMongo($id) {
        return $this->mongo()->findOne(array(
            '_id' => $id
        ));
    }

    public function queue($id) {
        try {
            $this->mongo()->insert(array(
                '_id' => $this->getQueuedId($id),
                'ttl' => (time() + $this->queueTtl)
            ));
        } catch (MongoCursorException $e) {
            
        }
    }

    public function reCache($id) {
        try {
            $this->mongo()->insert(array(
                '_id' => $this->getReCacheId($id),
                'ttl' => (time() + $this->reCacheTtl)
            ));
        } catch (MongoCursorException $e) {
            
        }
    }

    public function isReCacheInProgress($id) {
        $id = $this->getReCacheId($id);

        $reCache = $this->mongo()->findOne(array(
            '$id' => $id
        ));

        if (!is_null($reCache)) {
            if ($reCache['ttl'] > time()) {
                return true;
            } else {
                $this->mongo()->remove(array('$id' => $id));
            }
        }
        return false;
    }

    public function isQueueInProgress($id) {
        $id = $this->getQueuedId($id);

        $reCache = $this->mongo()->findOne(array(
            '$id' => $id
        ));

        if (!is_null($reCache)) {
            if ($reCache['ttl'] > time()) {
                return true;
            } else {
                $this->mongo()->remove(array('$id' => $id));
            }
        }
        return false;
    }

    private function mongo() {
        if (empty($this->mongo)) {
            $db = $this->connect();
            $this->mongo = $db->selectCollection($this->collection);
        }
        return $this->mongo;
    }

    private function connect() {
        if (!empty($this->username) && !empty($this->password)) {
            $connection = new MongoClient('mongodb://' . $this->username . ':' . $this->password . '@' . $this->server . ':' . $this->port);
        } else {
            $connection = new MongoClient('mongodb://' . $this->server . ':' . $this->port);
        }
        return $connection->selectDB($this->database);
    }

    private function removeTempFiles($id) {
        $qId = $this->getQueuedId($id);
        $rId = $this->getReCacheId($id);

        $this->mongo()->remove(array('_id' => $qId));
        $this->mongo()->remove(array('_id' => $rId));
    }

}