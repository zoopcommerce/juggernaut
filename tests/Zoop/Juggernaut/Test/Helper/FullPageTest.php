<?php

namespace Zoop\Juggernaut\Test\Helper;

use Zoop\Juggernaut\Test\BaseTest;
use Zoop\Juggernaut\Helper\FullPage;
use Zoop\Juggernaut\Adapter\Memory\MemoryCachePool;
use Zoop\Juggernaut\Adapter\Memory\MemoryCacheItem;

class FullPageTest extends BaseTest
{
    public function testCacheMiss()
    {
        $cachePool = new MemoryCachePool();
        $fullPage = new FullPage($cachePool, 300, false, true);
    }
}
