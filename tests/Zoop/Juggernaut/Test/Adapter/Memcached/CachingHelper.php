<?php

require __DIR__ . '/../../../../../../vendor/autoload.php';

use \Memcached;
use Zoop\Juggernaut\Adapter\Memcached\MemcachedCachePool;

if (isset($argv[1]) && isset($argv[2])) {
    $key = str_replace('"', '', $argv[1]);
    $value = str_replace('"', '', $argv[2]);

    $sleep = isset($argv[3]) ? intval(str_replace('"', '', $argv[3])) : 3;

    $memcached = new Memcached;
    $memcached->addServer('localhost', 11211);

    $pool = new MemcachedCachePool($memcached);

    $item = $pool->getItem($key);
    if ($item->isHit() === false) {
        sleep($sleep);
        $item->set($value);
        $item->save();
    }
}
