<?php

namespace Zoop\Juggernaut\Test\Adapter\Memcached;

use \DateTime;
use \Memcached;
use \stdClass;
use Zoop\Juggernaut\Test\BaseTest;
use Zoop\Juggernaut\Test\MemcachedTrait;
use Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem;
use Zoop\Juggernaut\Adapter\Memcached\MemcachedCachePool;

class MemcachedCachePoolTest extends BaseTest
{
    use MemcachedTrait;

    public function setUp()
    {
        $memcached = new Memcached;
        $memcached->addServer(self::$MEMCACHED_HOST, self::$MEMCACHED_PORT);
        $this->setMemcached($memcached);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->getMemcached()->flush();
    }

    public function testCacheMiss()
    {
        $key = 'cache miss';
        $pool = new MemcachedCachePool($this->getMemcached());

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testSimpleCacheHit()
    {
        $pool = new MemcachedCachePool($this->getMemcached());
        $key = 'simple cache hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into memcached
        $item = new MemcachedCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $savedResult = $item->save();
        $this->assertTrue($savedResult);
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());
        
        //cached value
        $cachedValue = $item->get();
        $this->assertEquals($value, $cachedValue);
        $this->assertSame($value['subValue'], $cachedValue['subValue']);
    }

    public function testObjectCacheHit()
    {
        $pool = new MemcachedCachePool($this->getMemcached());
        $key = 'object cache hit';

        $value = new stdClass;
        $value->string = 'Some Object Name';
        $value->int = 100;
        $value->null = null;
        $value->boolean = false;

        //save cache into memcached
        $item = new MemcachedCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $savedResult = $item->save();
        $this->assertTrue($savedResult);
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem', $item);
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
        $pool = new MemcachedCachePool($this->getMemcached());
        $key = 'cache hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into memcached
        $item = new MemcachedCacheItem($pool, $key, $value, true, new DateTime('-1 Hour'));
        $savedResult = $item->save();
        $this->assertTrue($savedResult);
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testDeleteCache()
    {
        $pool = new MemcachedCachePool($this->getMemcached());
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into memcached
        $item = new MemcachedCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memcached\MemcachedCacheItem', $item);
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
        $pool = new MemcachedCachePool($this->getMemcached());
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into memcached
        $item = new MemcachedCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();

        $allKeys = $this->getMemcached()->getAllKeys();
        $this->assertCount(1, $allKeys);

        $pool->clear();

        $allKeys = $this->getMemcached()->getAllKeys();
        $this->assertCount(0, $allKeys);
    }

    public function testQueuingProcess()
    {
//        $pool = new MemcachedCachePool($this->getMemcached());
//        $key = 'testQueued1';
//        $value = 'testValue1';
//
//        $this->nonBlockingRequest(__DIR__ . '/CachingHelper.php "' . $key . '" "' . $value . '"');
//
//        //wait for script to kick in and queue
//        sleep(1);
//
//        $queued = $pool->isQueued($key);
//        $this->assertTrue($queued);
//
//        //get cache using the proper process.
//        //this will use the exponential backoff queue
//        $item = $pool->getItem($key);
    }

    public function testRecachingProcess()
    {
        //to do
    }
}
