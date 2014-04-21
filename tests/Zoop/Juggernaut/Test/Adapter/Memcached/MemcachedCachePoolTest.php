<?php

namespace Zoop\Juggernaut\Test\Adapter\Memcached;

use \DateTime;
use \Memcached;
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

    public function testCacheHit()
    {
        $pool = new MemcachedCachePool($this->getMemcached());
        $key = 'cache hit';
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
        $this->assertEquals($value, $item->get());
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
}
