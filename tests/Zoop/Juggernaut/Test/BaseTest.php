<?php

namespace Zoop\Juggernaut\Test;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {

    }

    protected function nonBlockingRequest($file)
    {
        $ex = sprintf('php %s > /dev/null 2>&1 &', $file);
        exec($ex);
    }
}
