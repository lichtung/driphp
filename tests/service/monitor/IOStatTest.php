<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/24 0024
 * Time: 11:18
 */

namespace sharin\tests\service\monitor;


use sharin\core\UnitTest;
use sharin\service\monitor\IOStatistics;

class IOStatTest extends UnitTest
{
    public function testRun()
    {
        IOStatistics::getInstance()->run();
    }
}