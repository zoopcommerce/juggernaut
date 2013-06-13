<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Helper\Database;

interface DatabaseInterface {

//    public function connect();

    public function query($query, $ttl = 0);

    public function numberOfRows($reference);

    public function fetchRow($queryId);

    public function getInsertedId();

    public function escape($string);
}