<?php

require __DIR__ . '/../../../vendor/autoload.php';

use \MongoClient;

$mongo = new MongoClient('mongodb://localhost:27017');
$mongoCollection = $mongo->selectCollection('juggernaut_test', 'Cache');

$mongoCollection->drop();