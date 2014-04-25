<?php

require __DIR__ . '/../../../../../../vendor/autoload.php';

use \MongoClient;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool;

if (isset($argv[1]) && isset($argv[2])) {
    $key = str_replace('"', '', $argv[1]);
    $value = str_replace('"', '', $argv[2]);
    $sleep = isset($argv[3]) ? intval(str_replace('"', '', $argv[3])) : 1;

    $mongo = new MongoClient('mongodb://localhost:27017');
    $mongoCollection = $mongo->selectCollection('juggernaut_test', 'Cache');

    $pool = new MongoDbCachePool($mongoCollection);

    $item = $pool->getItem($key);
    if ($item->isHit() === false) {
        sleep($sleep);
        $item->set($value);
        $item->save();
    }
}
