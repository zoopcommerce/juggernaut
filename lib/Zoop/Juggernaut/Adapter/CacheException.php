<?php

/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\Juggernaut\Adapter;

use \Exception;
use Psr\Cache\InvalidArgumentException;

class CacheException extends Exception implements InvalidArgumentException
{

}
