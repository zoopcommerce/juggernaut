<?php

namespace Zoop\Juggernaut\Test\Adapter\MongoDb;

require __DIR__ . '/../../../../../../vendor/autoload.php';

use \MongoClient;
use Zoop\Juggernaut\Adapter\MongoDb\MongoDbCachePool;

class CachingHelper
{
    protected static $MONGO_DATABASE = 'juggernaut_test';
    protected static $MONGO_COLLECTION = 'Cache';

    public static function initCache($key, $value, $sleep = 1)
    {
        $mongo = new MongoClient('mongodb://localhost:27017');
        $mongoCollection = $mongo->selectCollection(self::$MONGO_DATABASE, self::$MONGO_COLLECTION);

        $pool = new MongoDbCachePool($mongoCollection);

        $item = $pool->getItem($key);
        if ($item->isHit() === false) {
            sleep($sleep);
            $item->set($value);
            $item->save();
        }
        return $item;
    }

}

if (isset($argv[1]) && isset($argv[2])) {
    $key = str_replace('"', '', $argv[1]);
    $value = str_replace('"', '', $argv[2]);

    $sleep = isset($argv[3]) ? intval(str_replace('"', '', $argv[3])) : 3;
    $item = CachingHelper::initCache($key, $value, $sleep);
}