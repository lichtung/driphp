<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:21
 */

namespace driphp\test\library;

use driphp\library\traits\Macroable;
use driphp\tests\UniTest;


/**
 * Class Hello
 * @method sayHi() static
 * @package driphp\test\library
 */
class Hello
{
    use Macroable;
}

class MacroableTest extends UniTest
{

    public function testRun()
    {
        $hello = new Hello();
        $data = md5(sha1(microtime(true)));
        Hello::macro('sayHi', function () use ($data) {
            return $data;
        });
        $this->assertTrue($hello->sayHi() === $data);
        $this->assertTrue(Hello::sayHi() === $data);
    }


}
