<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Helper;

use Zoop\Cache\File;
use Zoop\Cache\CacheInterface;

class FullPage {

    private $handler;
    private $ttl = 300;
    private $auto = true;

    public function __construct(CacheInterface $handler = null, $ttl = 300, $auto = true) {
        if (is_null($handler)) {
            $this->handler = new File();
        } else {
            $this->handler = $handler;
        }

        $this->auto = $auto;
        $this->ttl = $ttl;

        if ($this->auto === true) {
            $this->start();
        }
    }

    public function start() {
        $name = $this->getFileName();

        $cache = $this->handler->get($name, true);

        if ($cache === false) {
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
        $this->handler->set($this->getFileName(), ob_get_contents(), $this->ttl);
        ob_end_flush();
    }

    public function setHandler(CacheInterface $handler) {
        $this->handler = $handler;
        return $this;
    }

    private function getFileName() {
        return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

}