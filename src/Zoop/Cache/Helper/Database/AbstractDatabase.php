<?php

namespace Zoop\Cache\Helper\Database;

use Zoop\Cache\CacheInterface;

abstract class AbstractDatabase {
    /* @var $cacheHandler CacheInterface */

    protected $cacheHandler = null;
    protected $log = [];
    protected $logQueries = false;
    protected $totalExecutionTime = 0;

    public function setCacheHandler(CacheInterface $handler) {
        $this->cacheHandler = $handler;
        return $this;
    }

    public function setLogQueries($allowLog) {
        $this->logQueries = (bool) $allowLog;
        return $this;
    }

}