<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Helper\Database\Mysqli;

use \stdClass;
use \Exception;
use \mysqli as db;
use Zoop\Juggernaut\Adapter\AdapterInterface;
use Zoop\Juggernaut\Helper\Database\AbstractDatabase;
use Zoop\Juggernaut\Helper\Database\DatabaseInterface;

class Mysqli extends AbstractDatabase implements DatabaseInterface
{
    protected $cache = array();
    protected $connection;
    protected $master;
    protected $slaves = [];
    protected $transactionInProgress = false;

    public function __construct(AdapterInterface $adapter = null, $logQueries = false)
    {
        if (!is_null($adapter)) {
            $this->setAdapter($adapter);
        }

        $this->setLogQueries($logQueries);
    }

    public function __destruct()
    {
        if ($this->displayErrors === true) {
            $errors = $this->getErrors();
            if (!empty($errors)) {
                trigger_error(print_r($errors, true), E_USER_ERROR);
            }
        }
    }

    /**
     * 
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param string $port
     * @param boolean $addMasterAsSlave
     */
    public function setMaster($host, $database, $user = null, $password = null, $port = 3306, $addMasterAsSlave = true)
    {
        $this->master = $this->connect($host, $database, $user, $password, $port);

        //add the master as a slave as well
        if ($addMasterAsSlave === true) {
            $this->addSlave($host, $database, $user, $password, $port);
        }
    }

    /**
     * 
     * @param type $host
     * @param type $user
     * @param type $password
     * @param type $database
     * @param type $port
     */
    public function addSlave($host, $database, $user, $password, $port = 3306)
    {
        $connection = new stdClass;

        $connection->host = $host;
        $connection->database = $database;
        $connection->user = $user;
        $connection->password = $password;
        $connection->port = $port;

        $this->slave[] = $connection;
    }

    /**
     * 
     * @param boolean $forceMaster
     * @param boolean $slaveKey
     * @return \mysqli
     * @throws Exception Cannot conect to any slave
     */
    public function getConnection($forceMaster = false, $slaveKey = false)
    {
        if ($forceMaster === true) {
            return $this->getMaster();
        }

        if (!isset($this->connection)) {
            $slaves = $this->getSlaves();

            if ($slaveKey === false) {
                $slaveKey = array_rand($slaves);
            }

            $slave = $slaves[$slaveKey];
            try {
                $this->connection = $this->connect($slave->host, $slave->database, $slave->user, $slave->password, $slave->post);
            } catch (Exception $e) {
                //remove failed connection
                unset($this->slaves[$slaveKey]);
                $this->slaves = array_values($this->slaves);

                if (!empty($this->slaves)) {
                    return $this->getConnection($forceMaster, $slaveKey);
                } else {
                    throw new Exception('Cannot conect to any slave');
                }
            }
        }

        return $this->connection;
    }

    /**
     * 
     * @return \mysqli
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * 
     * @return array
     */
    public function getSlaves()
    {
        return $this->slaves;
    }

    /**
     * 
     * @return boolean
     */
    public function getTransactionInProgress()
    {
        return $this->transactionInProgress;
    }

    /**
     * 
     * @param \mysqli $connection
     */
    public function setConnection(\mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * 
     * @param type $slaves
     */
    public function setSlaves($slaves)
    {
        $this->slaves = $slaves;
    }

    /**
     * 
     * @param boolean $transactionInProgress
     */
    public function setTransactionInProgress($transactionInProgress)
    {
        $this->transactionInProgress = (bool) $transactionInProgress;
    }

    /**
     * 
     * @param type $host
     * @param type $user
     * @param type $password
     * @param type $database
     * @param type $port
     * @return \mysqli
     */
    private function connect($host, $database, $user = null, $password = null, $port = 3306)
    {
        try {
            $port = !empty($port) ? intval($port) : 3306;
            $connection = new db($host, $user, $password, $database, $port);
            if ($connection->connect_errno) {
                throw new Exception('Cannot connect to ' . $host . '. Error: ' . $connection->connect_errno);
                return false;
            } else {
                $connection->set_charset("utf8");
            }
            return $connection;
        } catch (Exception $e) {
            throw new Exception('Cannot connect to ' . $host);
        }
    }

    /**
     * 
     * @param string $query
     * @param int $ttl
     * @return \mysqli_result
     */
    public function query($query, $ttl = 0)
    {
        $time = microtime(true);
        $cached = true;
        $ttl = intval($ttl);

        if (
                strpos($query, 'INSERT') !== false ||
                strpos($query, 'UPDATE') !== false ||
                strpos($query, 'DELETE') !== false ||
                $this->getTransactionInProgress() === true ||
                $ttl == 0
        ) {
            //only use master for writes
            $r = ($this->getMaster()) ? $this->getMaster()->query($query) : false;
            if ($r === false) {
                $this->addError($query);
            }
        } else {
            if (!is_null($this->getAdapter())) {
                $this->getAdapter()->setTtl($ttl);

                $r = $this->getAdapter()->getItem($query, $success);
                if ($success === true) {
                    $this->setCache($query, $r);
                } else {
                    $r = ($this->getConnection()) ? $this->getConnection()->query($query) : false;
                    if ($r !== false) {
                        $this->setCache($query, $r);
                    } else {
                        $this->addError($query);
                        //clear the queue to ensure no lingering queues which will prevent the next load
                        $this->getAdapter()->clearQueue($query);
                    }
                    $cached = false;
                }

                //set the resource id to the query so we can pull it from the file cache
                unset($r);
                $r = $query;
                $this->getAdapter()->normalizeKey($r);
            } else {
                $r = ($this->getConnection()) ? $this->getConnection()->query($query) : false;
                if ($r === false) {
                    $this->addError($query);
                }
            }
        }

        $executionTime = (microtime(true) - $time);
        if ($this->getLogQueries() === true) {
            $this->log[] = array(
                'time' => $executionTime,
                'query' => $query,
                'cached' => $cached
            );
        }
        $this->totalExecutionTime += $executionTime;

        return $r;
    }

    /**
     * 
     * @param string $key
     * @param object|\mysqli_result $result
     */
    private function setCache($key, $result)
    {
        $rawKey = $key;
        $this->getAdapter()->normalizeKey($key);
        if (!isset($this->cache[$key])) {
            if (gettype($result) == 'object' && is_a($result, 'mysqli_result')) {
                $data = $this->getAllRows($result);

                $this->getAdapter()->setItem($rawKey, $data);
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

    /**
     * 
     * @param \mysqli_result $result
     * @return array
     */
    private function getAllRows($result)
    {
        $data = array();
        while ($row = $this->fetchRow($result)) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * 
     * @param \mysqli_result $result
     * @return int
     */
    public function numberOfRows($result)
    {
        return ($result) ? $result->num_rows : 0;
    }

    /**
     * 
     * @param object|string $result
     * @return string|boolean
     */
    public function fetchRow($result)
    {
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

    public function getInsertedId()
    {
        return ($this->getConnection(true)) ? $this->getConnection(true)->insert_id : false;
    }

    public function escape($string)
    {
        return ($this->getConnection(true)) ? $this->getConnection(true)->real_escape_string($string) : $string;
    }

    public function affectedRows()
    {
        return ($this->getConnection(true)) ? $this->getConnection(true)->affected_rows : false;
    }

    public function getFields($result)
    {
        if (gettype($result) == 'object') {
            return $result->fetch_fields();
        } else if (is_array($result) && !empty($result)) {
            return array_keys($result);
        }

        return false;
    }

    public function transaction($status = 'begin')
    {
        switch ($status) {
            case 'begin':
                $this->setTransactionInProgress(true);
                return $this->getConnection(true)->autocommit(false);
                break;
            case 'commit':
                $this->setTransactionInProgress(false);
                return $this->getConnection(true)->commit();
                break;
            case 'rollback':
                $this->setTransactionInProgress(false);
                return $this->getConnection(true)->rollback();
                break;
        }

        return true;
    }

    public function beginTransaction()
    {
        $this->setTransactionInProgress(true);
        return $this->getConnection(true)->autocommit(false);
    }

    public function commitTransaction()
    {
        $this->setTransactionInProgress(false);
        return $this->getConnection(true)->commit();
    }

    public function rollbackTransaction()
    {
        $this->setTransactionInProgress(false);
        return $this->getConnection(true)->rollback();
    }

    public function close()
    {
        $slave = $this->getSlaves();
        $master = $this->getMaster();

        if (get_class($slave) == 'mysqli') {
            @$slave->close();
        }

        if (get_class($master) == 'mysqli') {
            @$master->close();
        }
        return true;
    }

}
