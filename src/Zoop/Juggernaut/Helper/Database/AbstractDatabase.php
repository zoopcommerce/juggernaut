<?php

namespace Zoop\Juggernaut\Helper\Database;

use Zoop\Juggernaut\Adapters\AdapterInterface;

abstract class AbstractDatabase {
    /* @var $adapter AdapterInterface */

    protected $adapter = null;
    protected $log = array();
    protected $logQueries = false;
    protected $totalExecutionTime = 0;

    public function setAdapter(AdapterInterface $handler) {
        $this->adapter = $handler;
        $this->adapter->setNamespace('sql');

        return $this;
    }

    public function setLogQueries($allowLog) {
        $this->logQueries = (bool) $allowLog;
        return $this;
    }

}