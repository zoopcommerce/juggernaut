<?php

namespace Zoop\Juggernaut\Test\Adapter\MongoDb;

use \DateTime;
use \MongoClient;
use Zoop\Juggernaut\Test\BaseTest;
use Zoop\Juggernaut\Test\MongoDbTrait;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem;

class MongoDbCachePoolTest extends BaseTest
{
    use MongoDbTrait;

    public function setUp()
    {
        $this->setMongoClient(new MongoClient('mongodb://localhost:27017'));
        $this->setMongoCollection(
            $this->getMongoClient()
                ->selectCollection(
                    self::$MONGO_DATABASE,
                    self::$MONGO_COLLECTION
                )
        );
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->getMongoCollection()->drop();
    }

    public function testCacheMiss()
    {
        $key = 'cache miss';
        $pool = new MongoDbCachePool($this->getMongoCollection());

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testCacheHit()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'cache hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MongoDbCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());
    }

    public function testStaleCacheMiss()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'stale cache miss';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MongoDbCacheItem($pool, $key, $value, true, new DateTime('-1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testDeleteCache()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MongoDbCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());

        //delete cache
        $item->delete();
        unset($item);

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
    }

    public function testClearCache()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MongoDbCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();

        $itemsCursor = $this->getMongoCollection()->find();
        $this->assertTrue($itemsCursor->count() > 0);

        $pool->clear();

        $itemsCursor = $this->getMongoCollection()->find();
        $this->assertTrue($itemsCursor->count() === 0);
    }
}
