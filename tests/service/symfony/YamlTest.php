<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 16:04
 */

namespace driphp\tests\service\symfony;


use driphp\service\symfony\Yaml;
use driphp\tests\UniTest;

class YamlTest extends UniTest
{

    public function testDump()
    {
        $str = Yaml::getInstance()->dump([
            'foo' => 'bar',
            'bar' => [
                'foo' => 'bar',
                'bar' => [
                    'foo' => 'bar',
                    'bar' => 'abc'
                ]
            ],
        ], 1);
        # 删除 \r
        $this->assertTrue(0 === strcmp(str_replace("\r", '', "foo: bar
bar:
    foo: bar
    bar: baz
"), $str));
        $this->assertTrue(true);
    }

}