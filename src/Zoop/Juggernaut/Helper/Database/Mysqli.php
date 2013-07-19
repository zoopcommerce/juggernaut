<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Helper\Database;

use \Exception;
use \mysqli as db;
use Zoop\Juggernaut\Adapter\AdapterInterface;

class Mysqli extends AbstractDatabase implements DatabaseInterface {

    protected $cache = array();
    protected $connection;
    protected $transactionInProgress = false;

    public function __construct(AdapterInterface $adapter = null, $logQueries = false) {
        if (!is_null($adapter)) {
            $this->setAdapter($adapter);
        }

        $this->setLogQueries($logQueries);
    }

    public function __destruct() {
        if ($this->displayErrors === true) {
            $errors = $this->getErrors();
            if (!empty($errors)) {
                trigger_error(print_r($errors, true), E_USER_ERROR);
            }
        }
    }

    public function connect($host, $user, $password, $database, $port = 3306, $persistency = false) {
        try {
            $port = !empty($port) ? intval($port) : 3306;
            $this->connection = new db($host, $user, $password, $database, $port);
            if ($this->connection->connect_errno) {
                die("Failed to connect to MySQL: " . $this->connection->connect_error);
            } else {
                $this->connection->set_charset("utf8");
            }
        } catch (Exception $e) {
            die('Cannot connect to the database');
        }
    }

    public function query($query, $ttl = 0) {
        $time = microtime(true);
        $cached = true;
        $ttl = intval($ttl);

        if (
                strpos($query, 'INSERT') !== false ||
                strpos($query, 'UPDATE') !== false ||
                strpos($query, 'DELETE') !== false ||
                $this->transactionInProgress === true ||
                $ttl == 0
        ) {
            $r = ($this->connection) ? $this->connection->query($query) : false;
            if ($r === false) {
                $this->addError($query);
            }
        } else {
            if (!is_null($this->adapter)) {
                $this->adapter->setTtl($ttl);

                $r = $this->adapter->getItem($query, $success);
                if ($success === true) {
                    $this->setCache($query, $r);
                } else {
                    $r = ($this->connection) ? $this->connection->query($query) : false;
                    if ($r !== false) {
                        $this->setCache($query, $r);
                    } else {
                        $this->addError($query);
                        //clear the queue to ensure no lingering queues which will prevent the next load
                        $this->adapter->clearQueue($query);
                    }
                    $cached = false;
                }

                //set the resource id to the query so we can pull it from the file cache
                unset($r);
                $r = $query;
                $this->adapter->normalizeKey($r);
            } else {
                $r = ($this->connection) ? $this->connection->query($query) : false;
                if ($r === false) {
                    $this->addError($query);
                }
            }
        }

        $executionTime = (microtime(true) - $time);
        if ($this->logQueries === true) {
            $this->log[] = array(
                'time' => $executionTime,
                'query' => $query,
                'cached' => $cached
            );
        }
        $this->totalExecutionTime += $executionTime;

        return $r;
    }

    private function setCache($key, $result) {
        $rawKey = $key;
        $this->adapter->normalizeKey($key);
        if (!isset($this->cache[$key])) {
            if (gettype($result) == 'object' && is_a($result, 'mysqli_result')) {
                $data = $this->getAllRows($result);

                $this->adapter->setItem($rawKey, $data);
                $this->cache[$key] = $data;
            } else {
                $this->cache[$key] = $result;
            }
        } else {
            if (is_array($this->cache[$key])) {
                reset($this->cache[$key]);
            }
        }
    }

    private function getAllRows($result) {
        $data = array();
        while ($row = $this->fetchRow($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function numberOfRows($result) {
        return ($result) ? $result->num_rows : 0;
    }

    public function fetchRow($result) {
        if (gettype($result) == 'object') {
            return ($result) ? $result->fetch_array(MYSQLI_ASSOC) : false;
        } else if (is_string($result)) {
            if (isset($this->cache[$result]) && !empty($this->cache[$result]) && is_array($this->cache[$result])) {
                $element = each($this->cache[$result]);
                if ($element !== false) {
                    return $element['value'];
                }
                next($this->cache[$result]);
            }
            return false;
        }
    }
    
    public function getInsertedId() {
        return ($this->connection) ? $this->connection->insert_id : false;
    }

    public function escape($string) {
        return ($this->connection) ? $this->connection->real_escape_string($string) : $string;
    }

    public function affectedRows() {
        return ($this->connection) ? $this->connection->affected_rows : false;
    }

    public function getFields($result) {
        return ($result) ? $result->fetch_fields() : false;
    }

    public function transaction($status = 'begin') {
        switch ($status) {
            case 'begin':
                $this->transactionInProgress = true;
                return $this->connection->autocommit(false);
                break;

            case 'commit':
                $this->transactionInProgress = false;
                return $this->connection->commit();
                break;

            case 'rollback':
                $this->transactionInProgress = false;
                return $this->connection->rollback();
                break;
        }

        return true;
    }

    public function beginTransaction() {
        $this->transactionInProgress = true;
        return $this->connection->autocommit(false);
    }

    public function commitTransaction() {
        $this->transactionInProgress = false;
        return $this->connection->commit();
    }

    public function rollbackTransaction() {
        $this->transactionInProgress = false;
        return $this->connection->rollback();
    }

    public function close() {
        return (get_class($this->connection) == 'mysqli') ? @$this->connection->close() : false;
    }

}