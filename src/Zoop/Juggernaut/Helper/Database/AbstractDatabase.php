<?php

namespace Zoop\Juggernaut\Helper\Database;

use Zoop\Juggernaut\Adapter\AdapterInterface;

abstract class AbstractDatabase {
    /* @var $adapter AdapterInterface */

    protected $adapter = null;
    protected $log = array();
    protected $errors = array();
    protected $logQueries = false;
    protected $displayErrors = false;
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

    public function setDisplayErrors($displayErrors) {
        $this->displayErrors = (bool) $displayErrors;
        return $this;
    }

    protected function addError($query) {
        $this->errors[] = [
            'query' => $query,
            'error' => $this->connection->error
        ];
        return $this;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getLog($orderBy = 'execution') {
        $queries = array();
        $time = array();
        $data = $this->log;

        if ($orderBy == 'slowest') {
            foreach ($this->log as $key => $row) {
                $queries[$key] = $row['query'];
                $time[$key] = $row['time'];
            }
            array_multisort($time, SORT_DESC, $queries, SORT_ASC, $data);
        }

        return array(
            'queries' => $data,
            'totalExecutionTime' => $this->totalExecutionTime
        );
    }

}