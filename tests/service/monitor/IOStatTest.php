<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/24 0024
 * Time: 11:18
 */

namespace driphp\tests\service\monitor;


use driphp\core\UnitTest;
use driphp\service\monitor\IOStatistics;

class IOStatTest extends UnitTest
{
    public function testRun()
    {
        IOStatistics::getInstance()->run();
    }
}