<?php

namespace Zoop\Juggernaut\Test\Adapter\MongoDb;

use \DateTime;
use \MongoClient;
use \stdClass;
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
                    self::$MONGO_DATABASE, self::$MONGO_COLLECTION
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

    public function testSimpleCacheHit()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'simple cache hit';
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
        
        //cached value
        $cachedValue = $item->get();
        $this->assertEquals($value, $cachedValue);
        $this->assertSame($value['subValue'], $cachedValue['subValue']);
    }

    public function testObjectCacheHit()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'object cache hit';

        $value = new stdClass;
        $value->string = 'Some Object Name';
        $value->int = 100;
        $value->null = null;
        $value->boolean = false;

        //save cache into mongo
        $item = new MongoDbCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());

        //cached value
        $cachedValue = $item->get();
        $this->assertInstanceOf('\stdClass', $cachedValue);
        $this->assertEquals($value, $cachedValue);
        $this->assertSame($value->string, $cachedValue->string);
        $this->assertSame($value->int, $cachedValue->int);
        $this->assertSame($value->null, $cachedValue->null);
        $this->assertSame($value->boolean, $cachedValue->boolean);
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

    public function testQueuingProcess()
    {
        $pool = new MongoDbCachePool($this->getMongoCollection());
        $key = 'testQueued1';
        $value = 'testValue1';

        $this->nonBlockingRequest(__DIR__ . '/CachingHelper.php "' . $key . '" "' . $value . '"');

        //wait for script to kick in and queue
        sleep(1);

        $queued = $pool->isQueued($key);
        $this->assertTrue($queued);
        
        //get cache using the proper process.
        //this will use the exponential backoff queue
        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());
    }

    public function testRecachingProcess()
    {
        //to do
    }
}
