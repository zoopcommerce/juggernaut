<?php

namespace Zoop\Juggernaut\Test;

use \MongoClient;
use \MongoCollection;

trait MongoDbTrait
{
    protected static $MONGO_DATABASE = 'juggernaut_test';
    protected static $MONGO_COLLECTION = 'Cache';
    protected $mongoClient;
    protected $mongoCollection;

    /**
     * @return MongoClient
     */
    public function getMongoClient()
    {
        return $this->mongoClient;
    }

    /**
     * @return MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->mongoCollection;
    }

    /**
     * @param MongoClient $mongoClient
     */
    public function setMongoClient(MongoClient $mongoClient)
    {
        $this->mongoClient = $mongoClient;
    }

    /**
     * @param MongoCollection $mongoCollection
     */
    public function setMongoCollection(MongoCollection $mongoCollection)
    {
        $this->mongoCollection = $mongoCollection;
    }
}
