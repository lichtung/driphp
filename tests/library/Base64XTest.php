<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 15:46
 */

namespace driphp\test\library;


use driphp\core\Chars;
use driphp\library\encrypt\Base64X;
use driphp\tests\UnitTest;

class Base64XTest extends UnitTest
{
    public function testRun()
    {
        $base64x = Base64X::factory();

        $i = 0;
        while ($i++ <= 4097) { # 1-4096
            $str = Chars::random($i);

            $en = $base64x->encode($str);
            $de = $base64x->decode($en);

            $this->assertTrue($str !== $en);
            $this->assertTrue($en !== $de);
            $this->assertTrue($str === $de);
        }
        $this->assertTrue(true);
    }

}