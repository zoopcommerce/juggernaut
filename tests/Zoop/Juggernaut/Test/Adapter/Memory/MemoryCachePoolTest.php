<?php

namespace Zoop\Juggernaut\Test\Adapter\Memory;

use \DateTime;
use \ReflectionClass;
use Zoop\Juggernaut\Test\BaseTest;
use Zoop\Juggernaut\Adapter\Memory\MemoryCachePool;
use Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem;

class MemoryCachePoolTest extends BaseTest
{
    public function testCacheMiss()
    {
        $key = 'cache miss';
        $pool = new MemoryCachePool();

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testCacheHit()
    {
        $pool = new MemoryCachePool();
        $key = 'cache hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MemoryCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());
    }

    public function testStaleCacheMiss()
    {
        $pool = new MemoryCachePool();
        $key = 'stale cache miss';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MemoryCacheItem($pool, $key, $value, true, new DateTime('-1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testDeleteCache()
    {
        $pool = new MemoryCachePool();
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MemoryCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem', $item);
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
        $pool = new MemoryCachePool();
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new MemoryCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $reflectPool = new ReflectionClass($pool);
        $property = $reflectPool->getProperty('data');
        $property->setAccessible(true);
        $data = $property->getValue($pool);

        $this->assertCount(1, $data);

        $pool->clear();
        $data = $property->getValue($pool);
        $this->assertCount(0, $data);
    }
}
