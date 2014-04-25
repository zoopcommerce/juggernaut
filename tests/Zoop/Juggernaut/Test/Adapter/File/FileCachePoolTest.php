<?php

namespace Zoop\Juggernaut\Test\Adapter\File;

use \DateTime;
use \stdClass;
use Zoop\Juggernaut\Test\BaseTest;
use Zoop\Juggernaut\Adapter\File\FileCachePool;
use Zoop\Juggernaut\Adapter\File\FileCacheItem;

class FileCachePoolTest extends BaseTest
{
    const DIRECTORY = 'data/temp';

    public function tearDown()
    {
        parent::tearDown();
        $pool = new FileCachePool($this->getDirectory());
        $pool->clear();
    }

    protected function getDirectory()
    {
        return __DIR__ . '/../../../../../../' . self::DIRECTORY;
    }

    public function testCacheMiss()
    {
        $key = 'cache miss';
        $pool = new FileCachePool($this->getDirectory());

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\File\FileCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testSimpleCacheHit()
    {
        $pool = new FileCachePool($this->getDirectory());
        $key = 'simple cache hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new FileCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\File\FileCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertTrue($item->isHit());

        //cached value
        $cachedValue = $item->get();
        $this->assertEquals($value, $cachedValue);
        $this->assertSame($value['subValue'], $cachedValue['subValue']);
    }

    public function testObjectCacheHit()
    {
        $pool = new FileCachePool($this->getDirectory());
        $key = 'object cache hit';

        $value = new stdClass;
        $value->string = 'Some Object Name';
        $value->int = 100;
        $value->null = null;
        $value->boolean = false;

        //save cache into mongo
        $item = new FileCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\File\FileCacheItem', $item);
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
        $pool = new FileCachePool($this->getDirectory());
        $key = 'stale cache miss';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new FileCacheItem($pool, $key, $value, true, new DateTime('-1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\File\FileCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testDeleteCache()
    {
        $pool = new FileCachePool($this->getDirectory());
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new FileCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();
        unset($item);

        $item = $pool->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\File\FileCacheItem', $item);
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
        $pool = new FileCachePool($this->getDirectory());
        $key = 'cache hit 2';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save cache into mongo
        $item = new FileCacheItem($pool, $key, $value, true, new DateTime('+1 Hour'));
        $item->save();

        $this->assertTrue($this->getNumberOfFiles() === 1);

        $pool->clear();

        $this->assertTrue($this->getNumberOfFiles() === 0);
    }

    public function testQueuingProcess()
    {
        //to do
    }

    public function testRecachingProcess()
    {
        //to do
    }

    protected function getNumberOfFiles()
    {
        $i = 0;
        foreach (glob($this->getDirectory() . '/*') as $file) {
            if (is_file($file)) {
                $i++;
            }
        }
        return $i;
    }
}
