<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Helper;

use Zoop\Cache\Adapters\FileSystem;
use Zoop\Cache\AdapterInterface;

class FullPage {

    private $handler;
    private $auto = true;

    public function __construct(AdapterInterface $handler = null, $ttl = 300, $auto = true) {
        if (is_null($handler)) {
            $this->handler = new FileSystem();
        } else {
            $this->handler = $handler;
        }
        $this->handler->setTtl($ttl);

        $this->auto = $auto;

        if ($this->auto === true) {
            $this->start();
        }
    }

    public function start() {
        $key = $this->getFileName();

        $cache = $this->handler->getItem($key, $success);

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
        $this->handler->setItem($this->getFileName(), ob_get_contents());
        ob_end_flush();
    }

    public function setHandler(AdapterInterface $handler) {
        $this->handler = $handler;
        return $this;
    }

    private function getFileName() {
        return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

}