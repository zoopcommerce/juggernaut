<?php

require __DIR__ . '/../../../../../../vendor/autoload.php';

use Zoop\Juggernaut\Adapter\File\FileCachePool;

if (isset($argv[1]) && isset($argv[2])) {
    $key = str_replace('"', '', $argv[1]);
    $value = str_replace('"', '', $argv[2]);
    $sleep = isset($argv[3]) ? intval(str_replace('"', '', $argv[3])) : 1;

    $dir = __DIR__;
    $pool = new FileCachePool($dir);

    $item = $pool->getItem($key);
    if ($item->isHit() === false) {
        sleep($sleep);
        $item->set($value);
        $item->save();
    }
}
