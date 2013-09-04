<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Helper;

use Zoop\Juggernaut\Adapter\FileSystem;
use Zoop\Juggernaut\Adapter\AdapterInterface;

class FullPage {

    private $adapter;
    private $auto = true;
    private $compress = true;

    public function getAuto()
    {
        return $this->auto;
    }

    public function setAuto($auto)
    {
        $this->auto = $auto;
    }

    public function getCompress()
    {
        return $this->compress;
    }

    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    public function __construct(AdapterInterface $adapter = null, $ttl = 300, $auto = true, $compress = true)
    {
        if (is_null($adapter)) {
            $this->adapter = new FileSystem();
        } else {
            $this->adapter = $adapter;
        }
        $this->adapter->setTtl($ttl);

        $this->auto = $auto;
        $this->compress = $compress;
    }

    public function setTtl($ttl)
    {
        $this->adapter->setTtl($ttl);
    }

    public function start()
    {
        //Full page cache should only be used for a GET request
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            throw new \Exception('Juggeranut full page cache helper should only be used for GET requests');
        }

        $key = $this->getFileName();
        $cache = $this->adapter->getItem($key, $success);

        if ($success === false) {
            ob_start();
            if ($this->auto === true) {
                register_shutdown_function(array($this, 'end'));
            }
        } else {
            echo $cache;
            exit(0);
        }
    }

    public function end()
    {
        $html = ob_get_contents();
        if ($this->compress) {
            //remove new lines
            $html = str_replace("\n", '', $html);
            //remove double spaces
            $html = preg_replace("/\s{2,}/", '', $html);
        }
        $this->adapter->setItem($this->getFileName(), $html);
        ob_end_flush();
    }

    public function setHandler(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    private function getFileName()
    {
        if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $fileName = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $fileName;
    }
}