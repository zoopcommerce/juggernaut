<?php

require __DIR__ . '/../../../vendor/autoload.php';

use \Memcached;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$memcached->flush();