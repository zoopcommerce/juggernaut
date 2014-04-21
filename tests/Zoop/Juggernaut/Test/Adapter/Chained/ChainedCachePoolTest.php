<?php

namespace Zoop\Juggernaut\Test\Adapter\Chained;

use \DateTime;
use \MongoClient;
use Zoop\Juggernaut\Test\BaseTest;
use Zoop\Juggernaut\Test\MongoDbTrait;
use Zoop\Juggernaut\Adapter\Chained\ChainedCachePool;
use Zoop\Juggernaut\Adapter\Memory\MemoryCachePool;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool;
use Psr\Cache\CacheItemPoolInterface;

class ChainedCachePoolTest extends BaseTest
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

    public function testSimpleCacheMiss()
    {
        $key = 'cache miss';

        $simple = $this->getSimpleChainedCachePool();

        $item = $simple->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Chained\ChainedCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testSimpleCacheHit()
    {
        $key = 'cache hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        $simple = $this->getSimpleChainedCachePool();

        $item = $simple->getItem($key);
        $item->set($value, new DateTime('+1 Hour'));
        $item->save();

        $item2 = $simple->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Chained\ChainedCacheItem', $item);
        $this->assertEquals($key, $item2->getKey());
        $this->assertTrue($item2->isHit());
        $this->assertEquals($value, $item2->get());
    }

    public function testMultiCacheMiss()
    {
        $key = 'cache miss';

        $multi = $this->getMultiChainedCachePool();

        $item = $multi->getItem($key);

        $this->assertInstanceOf('Zoop\Juggernaut\Adapter\Chained\ChainedCacheItem', $item);
        $this->assertEquals($key, $item->getKey());
        $this->assertFalse($item->isHit());
    }

    public function testMultiCacheCascadeSave()
    {
        $key = 'cache multi save';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        $multi = $this->getMultiChainedCachePool();

        $item = $multi->getItem($key);
        $item->set($value, new DateTime('+1 Hour'));
        $item->save();

        $pools = $multi->getCachePools();

        /* @var $pool CacheItemPoolInterface */
        foreach ($pools as $pool) {
            $item = $pool->getItem($key);
            $this->assertTrue($item->isHit());
            $this->assertEquals($value, $item->get());
        }
    }

    public function testMultiCachePrimaryHit()
    {
        $key = 'cache multi primary hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        $multi = $this->getMultiChainedCachePool();

        $item = $multi->getItem($key);
        $item->set($value, new DateTime('+1 Hour'));
        $item->save();

        $item = $multi->getItem($key);
        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());

        // test that we got the cache from the memory pool
        $this->assertEquals('Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem', $item->getClass());
    }

    public function testMultiCacheSecondaryHit()
    {
        $key = 'cache multi secondary hit';
        $value = [
            'subValue' => rand(100, 1000)
        ];

        //save it to mongo first
        $mongoDbPool = $this->getMongoDbCachePool();
        $item = $mongoDbPool->getItem($key);
        $item->set($value, new DateTime('+1 Hour'));
        $item->save();

        $multi = $this->getMultiChainedCachePool();

        $item = $multi->getItem($key);
        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());

        // test that we got the cache from the Mongo pool
        $this->assertEquals('Zoop\Juggernaut\Adapter\MongoDb\MongoDbCacheItem', $item->getClass());
    }

    /**
     * @return ChainedCachePool
     */
    public function getSimpleChainedCachePool()
    {
        $chainedCachePool = new ChainedCachePool();
        $memoryPool = new MemoryCachePool();
        $chainedCachePool->addCachePool($memoryPool);

        return $chainedCachePool;
    }

    /**
     * @return ChainedCachePool
     */
    public function getMultiChainedCachePool()
    {
        $chainedCachePool = new ChainedCachePool();

        $memoryPool = new MemoryCachePool();
        $mongoPool = $this->getMongoDbCachePool();

        $chainedCachePool->addCachePool($memoryPool);
        $chainedCachePool->addCachePool($mongoPool);

        return $chainedCachePool;
    }

    /**
     * @return MongoDbCachePool
     */
    public function getMongoDbCachePool()
    {
        return new MongoDbCachePool($this->getMongoCollection());
    }
}
