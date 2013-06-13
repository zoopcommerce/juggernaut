<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Cache;

class File extends AbstractCache implements CacheInterface {

    //it's best to have your caching directory below the web root for security
    private $cacheDirectory = '/tmp';

    public function __construct() {
        
    }

    public function set($name, $value, $ttl = 600) {
        //save the value to the local class cache
        parent::set($name, $value, $ttl);

        $fileName = $this->getFileName($name);

        $this->writeToFile($fileName, $value, $ttl + time());
    }

    public function get($name, $queue = true) {
        //check to see if it's already been cached in the class
        $value = parent::get($name, $queue);

        if ($value === false) {
            $fileName = $this->getFileName($name);

            if ($queue === true && !file_exists($fileName)) {
                if ($this->isQueueInProgress($fileName) === true) {
                    //anonymous function to test if we should continue to wait
                    $condition = function() use ($fileName) {
                                return !file_exists($fileName);
                            };
                    $wait = $this->wait($condition);
                    if ($wait === false) {
                        return false;
                    }
                } else {
                    $this->queue($fileName);
                }
            }

            $cache = $this->readFromFile($fileName);

            //check ttl
            if ($cache['value'] !== false && $cache['ttl'] < time() && $this->isReCacheInProgress($fileName) === false) {
                //set the queue
                $this->reCache($fileName);
                return false;
            } else {
                return $cache['value'];
            }
        } else {
            return $value;
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

    private function getFileName($name) {
        return $this->cacheDirectory . '/' . $this->namespace . '/' . $this->getId($name) . '.php';
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
        $ttl = 0;
        $value = false;

        if (file_exists($fileName)) {
            $contents = file_get_contents($fileName);

            list($exit, $ttl, $rawValue) = explode("\n", $contents, 3);
            $value = $this->decodeValue($rawValue);
            unset($exit, $rawValue);
        }
        return $parts = [
            'ttl' => $ttl,
            'value' => $value
        ];
    }

    private function writeToFile($fileName, $value, $ttl) {
        try {
            if ($this->isDir()) {
                $content = '<?php exit(0); ?>' . "\n";
                $content .= $ttl . "\n";
                $content .= $this->encodeValue($value);

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