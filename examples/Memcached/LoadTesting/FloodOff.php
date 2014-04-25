<?php

require __DIR__ . '/../../../vendor/autoload.php';

use \Memcached;
use Zoop\Juggernaut\Adapter\Memcached\MemcachedCachePool;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$valueFunction = function() {
    for ($i = 0; $i < 100000; $i++) {
        $value = md5(time() . mt_rand(1, 100000000));
    }
    return $value;
};

$pool = new MemcachedCachePool($memcached, false);
$key = 'someKey';
$item = $pool->getItem($key);

if ($item->isHit() === false) {
    //perform time consuming function
    $item->set($valueFunction());
    $item->save();
}
echo $item->get();
exit();
