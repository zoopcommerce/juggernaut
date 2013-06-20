<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapters;

class FileSystem extends AbstractAdapter implements AdapterInterface {

    //it's best to have your caching directory below the web root for security
    private $cacheDirectory = '/tmp/cache';

    public function __construct() {
        
    }

    public function setItem($key, $value) {
        //save the value to the local class cache
        parent::setItem($key, $value);
        $fileName = $this->getFileName($key);

        $this->writeToFile($fileName, $value);
    }

    public function getItem($key, &$success = null, $queue = true) {
        //check to see if it's already been cached in the class
        $value = parent::getItem($key, $success, $queue);

        if ($success === true) {
            return $value;
        } else {
            $fileName = $this->getFileName($key);

            if ($queue === true && !file_exists($fileName)) {
                if ($this->isQueueInProgress($fileName) === true) {
                    //anonymous function to test if we should continue to wait
                    $condition = function() use ($fileName) {
                                return !file_exists($fileName);
                            };
                    $wait = $this->wait($condition);
                    if ($wait === false) {
                        $success = false;
                        return null;
                    } else {
                        $success = true;
                        return $this->readFromFile($fileName);
                    }
                } else {
                    $this->queue($fileName);
                    $success = false;
                    return null;
                }
            } else {
                $ttl = $this->getTtl($key);

                if ($ttl < time() && $this->isReCacheInProgress($fileName) === false) {
                    //set the queue
                    $this->reCache($fileName);
                    $success = false;
                    return null;
                } else {
                    $success = true;
                    $value = $this->readFromFile($fileName);

                    return $value;
                }
            }
        }
    }

    public function queue($fileName) {
        if ($this->isDir()) {
            $queueFile = $this->getQueuedId($fileName);
            if (!is_file($queueFile)) {
                file_put_contents($queueFile, (time() + $this->queueTtl));
            }
        }
    }

    public function reCache($fileName) {
        if ($this->isDir()) {
            $queueFile = $this->getReCacheId($fileName);
            if (!is_file($queueFile)) {
                file_put_contents($queueFile, (time() + $this->reCacheTtl));
            }
        }
    }

    public function isReCacheInProgress($fileName) {
        $reCacheFile = $this->getReCacheId($fileName);
        if (is_file($reCacheFile)) {
            $ttl = file_get_contents($reCacheFile);
            if ($ttl > time()) {
                return true;
            } else {
                @unlink($reCacheFile);
            }
        }
        return false;
    }

    public function isQueueInProgress($fileName) {
        $queueFile = $this->getQueuedId($fileName);
        if (is_file($queueFile)) {
            $ttl = file_get_contents($queueFile);
            if ($ttl > time()) {
                return true;
            } else {
                @unlink($queueFile);
            }
        }
        return false;
    }

    private function getFileName($key) {
        $this->normalizeKey($key);
        return $this->cacheDirectory . '/' . $this->namespace . '/' . $key . '.php';
    }

    public function setCacheDirectory($cacheDirectory) {
        $this->cacheDirectory = $cacheDirectory;
        return $this;
    }

    public function setCacheFilePrefix($namespace) {
        $this->namespace = $namespace;
        return $this;
    }

    private function readFromFile($fileName) {
        if (file_exists($fileName)) {
            return $this->decodeValue(file_get_contents($fileName));
        }

        return null;
    }

    public function getTtl($key) {
        $fileName = $this->getFileName($key);
        if (is_file($fileName) && $this->ttl != 0) {
            return filemtime($fileName) + $this->ttl;
        }
        return 0;
    }

    private function writeToFile($fileName, $value) {
        try {
            if ($this->isDir()) {
                $content = $this->encodeValue($value);

                file_put_contents($fileName, $content, LOCK_EX);

                $this->removeTempFiles($fileName);
            }
        } catch (\Exception $e) {
            $this->removeTempFiles($fileName);
        }
    }

    private function removeTempFiles($fileName) {
        $queingFile = $this->getQueuedId($fileName);
        if (is_file($queingFile)) {
            @unlink($queingFile);
        }

        $reCacheFile = $this->getReCacheId($fileName);
        if (is_file($reCacheFile)) {
            @unlink($reCacheFile);
        }
    }

    private function isDir() {
        if (!is_dir($this->cacheDirectory . '/' . $this->namespace)) {
            return mkdir($this->cacheDirectory . '/' . $this->namespace, 0755, true);
        }
        return true;
    }

}