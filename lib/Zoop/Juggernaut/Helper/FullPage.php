<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Helper;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class FullPage
{
    protected $cachePool;
    protected $autoFlush = true;
    protected $compress = true;
    protected $ttl = 300;
    protected $cacheItem;

    public function __construct(CacheItemPoolInterface $cachePool, $ttl = 300, $autoFlush = true, $compress = true)
    {
        $this->setCachePool($cachePool);
        $this->setAutoFlush($autoFlush);
        $this->setCompress($compress);
        $this->setTtl($ttl);
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCachePool()
    {
        return $this->cachePool;
    }

    /**
     * @param CacheItemPoolInterface $cachePool
     */
    public function setCachePool(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * @return CacheItemInterface
     */
    public function getCacheItem()
    {
        return $this->cacheItem;
    }

    /**
     * @param CacheItemInterface $cacheItem
     */
    public function setCacheItem(CacheItemInterface $cacheItem)
    {
        $this->cacheItem = $cacheItem;
    }

    /**
     * @return boolean
     */
    public function hasAutoFlush()
    {
        return $this->autoFlush;
    }

    /**
     * @param boolean $autoFlush
     */
    public function setAutoFlush($autoFlush)
    {
        $this->autoFlush = (boolean) $autoFlush;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Number of seconds for the cache to live
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = intval($ttl);
    }

    /**
     * @return boolean
     */
    public function hasCompress()
    {
        return $this->compress;
    }

    /**
     * @param boolean $compress
     */
    public function setCompress($compress)
    {
        $this->compress = (boolean) $compress;
    }

    /**
     * Starts the output buffer and print if cache hit
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function start()
    {
        $key = $this->getKey();
        $cacheItem = $this->getCachePool()->getItem($key);

        if ($cacheItem->isHit() === false) {
            ob_start();
            if ($this->hasAutoFlush() === true) {
                $this->setCacheItem($cacheItem);
                register_shutdown_function(array($this, 'end'));
            }
        } else {
            echo $cacheItem->get();
            exit(0);
        }
    }

    /**
     * Catches the page output
     */
    public function end()
    {
        $cacheItem = $this->getCacheItem();

        $html = ob_get_contents();
        if ($this->hasCompress()) {
            //remove new lines
            $html = str_replace("\n", '', $html);
            //remove whitespace
            $html = preg_replace('~>\s+<~m', '><', $html);
        }
        $cacheItem->set($html, $this->getTtl());

        ob_end_flush();
    }

    /**
     * Creates a key from the current url
     *
     * @return string
     */
    protected function getKey()
    {
        $httpForwarded = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO');
        $https = filter_input(INPUT_SERVER, 'HTTPS');

        if (strtolower($https) === 'on' || strtolower($httpForwarded) === 'https') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        return $protocol . filter_input(INPUT_SERVER, 'HTTP_HOST') . filter_input(INPUT_SERVER, 'REQUEST_URI');
    }
}
