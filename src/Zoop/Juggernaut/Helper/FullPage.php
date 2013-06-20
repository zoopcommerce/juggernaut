<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Helper;

use Zoop\Juggernaut\Adapters\FileSystem;
use Zoop\Juggernaut\Adapters\AdapterInterface;

class FullPage {

    private $adapter;
    private $auto = true;
    private $compress = true;

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

    public function setTtl($ttl) {
        $this->adapter->setTtl($ttl);
    }

    public function start() {
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

    public function end() {
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

    public function setHandler(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    private function getFileName() {
        return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

}