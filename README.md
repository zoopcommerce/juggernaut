#Juggernaut

## Introduction

Juggernaut is a super fast PHP cache. It has a number of storage adapters and a few simple helpers to get you up and caching quickly.

One of the best aspects of this module is that it provides **flood protection** both on the initial cache creation - *by queuing subsequent requests* - but also on re-caching - *by serving old cache for subsequent requests until the new cache has been re-created*.

These two features allow Juggernaut to be at least **100%** faster than the [Zend Framework 2 cache adapters](http://framework.zend.com/manual/2.2/en/modules/zend.cache.storage.adapter.html) in normal use, and up to **270%** faster on highly concurrent applications.

## Installation

Install the module using Composer into your application's vendor directory. Add the following line to your
`composer.json`.

```json
{
    "require": {
        "zoopcommerce/juggernaut": "dev-master"
    }
}
```

## Usage

Juggernaut implements the proposed (but not yet accepted) PSR Cache specification: https://github.com/Crell/Cache

### Key-Value-Pair Caching
#### File System
```php
$dir = __DIR__ . '/data/cache';
$cachePool = new Zoop\Juggernaut\Adapter\File\FileCachePool($dir);

$key = 'yourUniqueKey';

$item = $pool->getItem($key);

// check if cache hit/miss
if ($item->isHit()) {
    // cache missed so now we have to execute
    // some query that takes a long time
    sleep(1);
    $data = rand(0, 10000);

    //save it to cache
    $item->set($data, new DateTime('+1 hour'));
    $item->save();
    echo $data;
} else {
    // cache value
    echo $item->get();
}
```
#### Memcached
```php
$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$cachePool = new Zoop\Juggernaut\Adapter\Memcached\MemcachedCachePool($memcached);

$key = 'yourUniqueKey';

$item = $pool->getItem($key);

// check if cache hit/miss
if ($item->isHit()) {
    // cache missed so now we have to execute
    // some query that takes a long time
    sleep(1);
    $data = rand(0, 10000);

    //save it to cache
    $item->set($data, new DateTime('+1 hour'));
    $item->save();
    echo $data;
} else {
    // cache value
    echo $item->get();
}
```
#### Memory
```php
$cachePool = new Zoop\Juggernaut\Adapter\Memory\MemoryCachePool();

$key = 'yourUniqueKey';

$item = $pool->getItem($key);

// check if cache hit/miss
if ($item->isHit()) {
    // cache missed so now we have to execute
    // some query that takes a long time
    sleep(1);
    $data = rand(0, 10000);

    //save it to cache
    $item->set($data, new DateTime('+1 hour'));
    $item->save();
    echo $data;
} else {
    // cache value
    echo $item->get();
}
```
#### MongoDB
```php
$mongo = new MongoClient('mongodb://localhost:27017');
$mongoCollection = $mongo->selectCollection('myDb', 'myCacheCollection');

$cachePool = new Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool($mongoCollection);

$key = 'yourUniqueKey';

$item = $pool->getItem($key);

// check if cache hit/miss
if ($item->isHit()) {
    // cache missed so now we have to execute
    // some query that takes a long time
    sleep(1);
    $data = rand(0, 10000);

    //save it to cache
    $item->set($data, new DateTime('+1 hour'));
    $item->save();
    echo $data;
} else {
    // cache value
    echo $item->get();
}
```
#### MySQL
```php
//coming soon
```
#### Chained
You can chain cache pools together using the special `ChainedCachePool` class. This can be extremely useful for first checking fast local memory cache, then checking slower remote cache.
```php
$chainedCachePool = new Zoop\Juggernaut\Adapter\Chained\ChainedCachePool();

$memoryPool = new Zoop\Juggernaut\Adapter\Memory\MemoryCachePool();

$mongo = new MongoClient('mongodb://localhost:27017');
$mongoCollection = $mongo->selectCollection('myDb', 'myCacheCollection');

$mongoPool = new Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool($mongoCollection);

$chainedCachePool->addCachePool($memoryPool);
$chainedCachePool->addCachePool($mongoPool);

//now we can use it in the same way as before

$key = 'yourUniqueKey';

$item = $pool->getItem($key);

// check if cache hit/miss
if ($item->isHit()) {
    // cache missed so now we have to execute
    // some query that takes a long time
    sleep(1);
    $data = rand(0, 10000);

    //save it to cache
    $item->set($data, new DateTime('+1 hour'));
    $item->save();
    echo $data;
} else {
    //check which pool the cache hit came from
    echo $item->getClass();

    // cache value
    echo $item->get();
}
```

### Helpers
There are a few helpers that will expidite the usage of Juggernaut.
#### Full Page
As the name suggests, the "Full Page" helper will store the rendered page directly to cache. This results in blindingly fast page loads.

To use this script just place the following at the top of your pages.
```php
$pageTtl = 600; //10 mins
$cachePool = new Zoop\Juggernaut\Adapter\Memory\MemoryCachePool();

$pageCache = new Zoop\Juggernaut\Helper\FullPage($cachePool, $pageTtl);
$pageCache->start();
```
You can use any of the provided cache pools to store the full page cache.

There's no need to manually save the rendered page to cache, as the script will automatically flush the page output to the cache pool once the script exits.
#### Database
#### MySQLi
You can use the mysqli helper to automatically cache your sql queries.
```php
//coming soon
```
As you can see you don't have to worry if the cache exists or not as the helper does all the heavy lifting.

## Coming soon
* APC cache pool
* File system cache pool
* Mysqli cache pool
* PDO cache pool
* Reinstate flood protection
