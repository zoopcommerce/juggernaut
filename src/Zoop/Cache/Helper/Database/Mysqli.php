<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Helper\Database;

use \Exception;
use \mysqli as db;
use Zoop\Cache\Adapters\AdapterInterface;

class Mysqli extends AbstractDatabase implements DatabaseInterface {

    private $cache = [];
    private $connection;

    public function __construct(AdapterInterface $adapter = null, $logQueries = false) {
        if (!is_null($adapter)) {
            $this->setAdapter($adapter);
        }

        $this->setLogQueries($logQueries);
    }

    public function connect($host, $user, $password, $database, $port = 3306, $persistency = false) {
        try {
            $port = !empty($port) ? int_val($port) : 3306;
            $this->connection = new db($host, $user, $password, $database, $port);
            if ($this->connection->connect_errno) {
                die("Failed to connect to MySQL: " . $this->connection->connect_error);
            }
        } catch (Exception $e) {
            die('Cannot connect to the database');
        }
    }

    public function query($query, $ttl = 0) {
        $time = microtime(true);
        $cached = true;

        if (strpos($query, 'INSERT') !== false || strpos($query, 'NOW()') !== false || strpos($query, 'UPDATE') !== false || strpos($query, 'DELETE') !== false) {
            $r = ($this->connection) ? $this->connection->query($query) : false;
        } else {
            if (!is_null($this->adapter) && $ttl != 0) {
                $this->adapter->setTtl($ttl);

                $r = $this->adapter->getItem($query, $success);
                if ($success === true) {
                    $this->setCache($query, $r);
                } else {
                    $r = ($this->connection) ? $this->connection->query($query) : false;
                    $this->setCache($query, $r);
                    $cached = false;
                }

                //set the resource id to the query so we can pull it from the file cache
                unset($r);
                $r = $query;
                $this->adapter->normalizeKey($r);
            } else {
                $r = ($this->connection) ? $this->connection->query($query) : false;
            }
        }

        $executionTime = (microtime(true) - $time);
        if ($this->logQueries === true) {
            $this->log[] = [
                'time' => $executionTime,
                'query' => $query,
                'cached' => $cached
            ];
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
        $data = [];
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

    public function transaction() {
        
    }

    public function close() {
        return ($this->connection) ? $this->connection->close() : false;
    }

    public function getLog($orderBy = 'execution') {
        $queries = [];
        $time = [];
        $data = $this->log;

        if ($orderBy == 'slowest') {
            foreach ($this->log as $key => $row) {
                $queries[$key] = $row['query'];
                $time[$key] = $row['time'];
            }
            array_multisort($time, SORT_DESC, $queries, SORT_ASC, $data);
        }

        return [
            'queries' => $data,
            'totalExecutionTime' => $this->totalExecutionTime
        ];
    }

}