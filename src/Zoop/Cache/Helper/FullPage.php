<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Helper;

use Zoop\Cache\Adapters\FileSystem;
use Zoop\Cache\Adapters\AdapterInterface;

class FullPage {

    private $adapter;
    private $auto = true;

    public function __construct(AdapterInterface $adapter = null, $ttl = 300, $auto = true) {
        if (is_null($adapter)) {
            $this->adapter = new FileSystem();
        } else {
            $this->adapter = $adapter;
        }
        $this->adapter->setTtl($ttl);

        $this->auto = $auto;

        if ($this->auto === true) {
            $this->start();
        }
    }

    public function start() {
        $key = $this->getFileName();
        $cache = $this->adapter->getItem($key, $success);

        if ($success === false) {
            ob_start();
            if ($this->auto === true) {
                register_shutdown_function([$this, 'end']);
            }
        } else {
            echo $cache;
            exit(0);
        }
    }

    public function end() {
        $this->adapter->setItem($this->getFileName(), ob_get_contents());
        ob_end_flush();
    }

    public function setHandler(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    private function getFileName() {
        return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

}