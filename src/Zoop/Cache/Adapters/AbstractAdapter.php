<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache\Adapters;

abstract class AbstractAdapter {

    const CODEC_AUTO = 'auto';
    const QUEUING_ID = 'queued';
    const RECACHE_ID = 'recache';

    protected $cache = [];
    protected $namespace = '';
    protected $valueEncoder = 'auto';
    protected $valueDecoder = 'auto';
    protected $queueWaitPeriod = 400000; /* 400ms */
    protected $queueMaxTries = 5;
    protected $reCacheTtl = 10; /* 10s */
    protected $queueTtl = 10; /* 10s */
    protected $ttl = 30; /* 30s */

    public function normalizeKey(&$key) {
        $key = md5($key);
    }

    public function getItem($key, &$success = null, $queue = true) {
        $this->normalizeKey($key);

        if (isset($this->cache[$key])) {
            $success = true;
            return $this->cache[$key];
        }
        $success = false;

        return null;
    }

    public function setItem($key, $value) {
        $this->normalizeKey($key);

        $this->cache[$key] = $value;
    }

    public function setTtl($ttl) {
        $this->ttl = intval($ttl);
    }

    protected function encodeValue($value) {
        if ($this->valueEncoder == self::CODEC_AUTO) {
            if (is_object($value) || is_array($value)) {
                return serialize($value);
            } else {
                return $value;
            }
        } else {
            if (is_callable($this->valueEncoder)) {
                return $this->valueEncoder();
            }
        }
    }

    protected function decodeValue($value) {
        if ($this->valueDecoder == self::CODEC_AUTO) {
            $data = @unserialize($value);
            if ($data !== false) {
                return $data;
            } else {
                return $value;
            }
        } else {
            if (is_callable($this->valueDecoder)) {
                return $this->valueDecoder();
            }
        }
    }

    public function setValueEncoder($encoder) {
        if (is_callable($encoder)) {
            $this->valueEncoder = $encoder;
        }
        return $this;
    }

    public function setValueDecoder($decoder) {
        if (is_callable($decoder)) {
            $this->valueDecoder = $decoder;
        }
        return $this;
    }

    public function setNamespace($namespace) {
        $this->namespace = $namespace;
        return $this;
    }

    public function setQueueWaitPeriod($queueWaitPeriod) {
        $this->queueWaitPeriod = intval($queueWaitPeriod);
        return $this;
    }

    public function setQueueMaxTries($queueMaxTries) {
        $this->queueMaxTries = intval($queueMaxTries);
        return $this;
    }

    // exponential backoff
    protected function wait($conditionFunction) {
        $numTries = 0;
        do {
            usleep($this->queueWaitPeriod + ($this->queueWaitPeriod * pow(2, $numTries)));
            $numTries++;

            if ($numTries >= $this->queueMaxTries) {
                return false;
            }
        } while ($conditionFunction());
        return true;
    }

    protected function getReCacheId($id) {
        return $id . '.' . self::RECACHE_ID;
    }

    protected function getQueuedId($id) {
        return $id . '.' . self::QUEUING_ID;
    }

}