<?php

require __DIR__ . '/../../../vendor/autoload.php';

use \MongoClient;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool;

$mongo = new MongoClient('mongodb://localhost:27017');
$mongoCollection = $mongo->selectCollection('juggernaut_test', 'Cache');

$valueFunction = function() {
    for ($i = 0; $i < 100000; $i++) {
        $value = md5(time() . mt_rand(1, 100000000));
    }
    return $value;
};

$pool = new MongoDbCachePool($mongoCollection, false);
$key = 'someKey';
$item = $pool->getItem($key);

if ($item->isHit() === false) {
    //perform time consuming function
    $item->set($valueFunction());
    $item->save();
}
echo $item->get();
exit();