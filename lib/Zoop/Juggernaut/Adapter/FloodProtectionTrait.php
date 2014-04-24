<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

trait FloodProtectionTrait
{
    protected static $QUEUED = 'queued';
    protected static $RECACHE = 'recache';
    protected $queueWaitPeriod = 50000; /* 50ms */
    protected $maxQueueTries = 5;
    protected $reCacheTtl = 10; /* 10s */
    protected $queueTtl = 10; /* 10s */
    protected $floodProtection = true;
    
    /**
     * 
     * @return boolean
     */
    public function hasFloodProtection()
    {
        return $this->floodProtection;
    }

    /**
     * 
     * @param boolean $floodProtection
     */
    public function setFloodProtection($floodProtection)
    {
        $this->floodProtection = (boolean) $floodProtection;
    }

    /**
     * Creates a queue key
     * 
     * @param string $key
     * @return string
     */
    public function getQueueKey($key)
    {
        return $key . '.' . self::$QUEUED;
    }

    /**
     * Creates a re-cache key
     * 
     * @param string $key
     * @return string
     */
    public function getReCacheKey($key)
    {
        return $key . '.' . self::$RECACHE;
    }
    
    /**
     * An exponential back off / retry function
     * to queue cache requests
     * 
     * @param function $conditionFunction
     * @return boolean
     */
    protected function wait($conditionFunction)
    {
        $numTries = 0;
        do {
            usleep($this->queueWaitPeriod + ($this->queueWaitPeriod * pow(2, $numTries)));
            $numTries++;

            if ($numTries >= $this->maxQueueTries) {
                return false;
            }
        } while ($conditionFunction());
        return true;
    }

    /**
     * The time we allow re-caching requests
     * 
     * @return int
     */
    protected function getReCacheTtl()
    {
        return $this->reCacheTtl;
    }

    /**
     * The time we will queue cache requests
     * 
     * @return int
     */
    protected function getQueueTtl()
    {
        return $this->queueTtl;
    }
    
    /**
     * 
     * @param int $reCacheTtl
     */
    public function setReCacheTtl($reCacheTtl)
    {
        $this->reCacheTtl = intval($reCacheTtl);
    }

    /**
     * 
     * @param int $queueTtl
     */
    public function setQueueTtl($queueTtl)
    {
        $this->queueTtl = intval($queueTtl);
    }
}
