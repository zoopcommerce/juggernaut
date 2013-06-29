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

You can use Juggernaut by directly instantiating a storage adapter and calling set/get.

### Key-Value-Pair Caching Using Storage Adapters
#### File System
```php
// you should always store the cache below the web root for security!!
$cacheDirectory = __DIR__ . '../cache';

$cache = new Zoop\Juggernaut\Adapters\FileSystem($cacheDirectory);

$key = 'yourUniqueKey';

$data = $cache->getItem($key, $success);

// check if cache hit/miss
if ($success === false) {
  // cache missed so now we have to execute
	// some query that takes a long time
	for($i=0;$i<1000000;$i++) {
    	$data = rand(0, 10000);
    }

	//save it to cache
    $cache->setItem($key, $data);
    echo $data;
} else {
	// cache hit!
    echo $data;
}
```
#### MongoDB
```php
$database='MyMongoDb';
$username='mymongouser';
$password='mymongopass';

$cache = new Zoop\Juggernaut\Adapters\MongoDB($database, $username, $password);

$key = 'yourUniqueKey';

$data = $cache->getItem($key, $success);

// check if cache hit/miss
if ($success === false) {
	// cache missed so now we have to execute
	// some query that takes a long time
	for($i=0;$i<1000000;$i++) {
    	$data = rand(0, 10000);
    }

	//save it to cache
    $cache->setItem($key, $data);
    echo $data;
} else {
	// cache hit!
    echo $data;
}
```
#### Memcached
```php
//coming soon
```
#### MySQL
```php
//coming soon
```
### Helpers
There are a few helpers that will expidite the usage of Juggernaut. 
#### Full Page
As the name suggests, the "Full Page" helper will store the rendered page directly to cache. This results in blindingly fast page loads.

To use this script just place the following at the top of your pages.
```php
$pageTtl = 600; //10 mins
$cacheDirectory = __DIR__ . '../cache';

$adapter = new Zoop\Juggernaut\Adapters\FileSystem($cacheDirectory);

$pageCache = new Zoop\Juggernaut\Helper\FullPage($adapter, $pageTtl);
```
You can use any of the provided adapters to store the full page cache. eg.
```php
$pageTtl = 600; //10 mins
$database='MyMongoDb';
$username='mymongouser';
$password='mymongopass';

$adapter = new Zoop\Juggernaut\Adapters\MongoDB($database, $username, $password);

$pageCache = new Zoop\Juggernaut\Helper\FullPage($adapter, $pageTtl);

```
There's no need to manually save the rendered page to cache as the script will automatically flush the page output to the cache adapter once the script exits.
#### Database
#### MySQLi
You can use the mysqli helper to automatically cache your sql queries.
```php
$cacheDirectory = __DIR__ . '../cache';
$adapter = new Zoop\Juggernaut\Adapters\FileSystem($cacheDirectory);

$db = new Zoop\Juggernaut\Helper\Database\Mysqli($cache);
$db->connect($host, $username, $passwd, $database);

$q="SELECT COUNT(`pageviews`) as 'pageviews' FROM `analytics` GROUP BY `date`";
$r = $db->query($q, 600); //second arg is ttl
if($r!==false) {
	$pageviews = $db->fetchRow($q)['pageviews'];
}
```
As you can see you don't have to worry if the cache exists or not as the helper does all the heavy lifting.


## Coming soon
* Unit tests
* Working examples
* MySQL adapter
* Memcached adapter
* MongoDB helper
