<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 12:05
 */
declare(strict_types=1);


namespace tests\core;


use PHPUnit\Framework\TestCase;
use driphp\core\Request;

class RequestTest extends TestCase
{

    public function testParsePathInfo()
    {
        $assertion = [
            'm1/cc/aa' => [
                [
                    'm1'
                ],
                'cc',
                'aa',
                '/', '/', '/'
            ],
            'm1/m2/cc/aa' => [
                [
                    'm1',
                    'm2'
                ],
                'cc',
                'aa',
                '/', '/', '/'
            ],
            'm1/m2/m3/cc/aa' => [
                [
                    'm1',
                    'm2',
                    'm3',
                ],
                'cc',
                'aa',
                '/', '/', '/'
            ],
            'cc/aa' => [
                [
                ],
                'cc',
                'aa',
                '/', '/', '/'
            ],
            'aa' => [
                [

                ],
                '',
                'aa',
                '/', '/', '/'
            ],
            '' => [
                [

                ],
                '',
                '',
                '/', '/', '/'
            ],

            ################

            'm1+cc-aa' => [
                [
                    'm1'
                ],
                'cc',
                'aa',
                '*', '+', '-'
            ],
            'm1*m2+cc-aa' => [
                [
                    'm1',
                    'm2'
                ],
                'cc',
                'aa',
                '*', '+', '-'
            ],
            'm1*m2*m3+cc-aa' => [
                [
                    'm1',
                    'm2',
                    'm3',
                ],
                'cc',
                'aa',
                '*', '+', '-'
            ],
            'cc-aa' => [
                [
                ],
                'cc',
                'aa',
                '*', '+', '-'
            ],
            'bb' => [
                [

                ],
                '',
                'bb',
                '*', '+', '-'
            ],
        ];
        foreach ($assertion as $key => $value) {
            list($m, $c, $a, $mm, $mc, $ma) = $value;
            list($pm, $pc, $pa) = Request::parsePathInfo($key, $mm, $mc, $ma);
            $this->assertTrue($pm === $m);
            $this->assertTrue($pc === $c);
            $this->assertTrue($pa === $a);
        }

        $this->assertTrue(true);
    }

}