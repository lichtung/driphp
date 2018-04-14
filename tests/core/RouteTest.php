<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 13:49
 */
declare(strict_types=1);


namespace tests\core;


use PHPUnit\Framework\TestCase;
use sharin\core\Route;

class RouteTest extends TestCase
{

    public function testMatch()
    {
        $items = [
            # 必备参数
            '/article/{num}' => [
                '/article/1' => [1],
                '/article/1/' => [1],
                '/article/asd' => false,
                '/article/1/close' => false,
            ],
            '/article/[num]/[any]' => [ # '/article(/[0-9]+)?(/[^/]+)?'
                '/article/1' => [1],
                '/article' => [],
                '/article/larry' => ['', 'larry'],
                '/article/123' => [123],
            ],
            '/article/{any}/{num}' => [
                '/article/salt/1' => ['salt', '1'],
                '/article/salty-water-/1/' => ['salty-water-', '1'],
                '/article/salty-water-4477/1/' => ['salty-water-4477', '1'],
                '/article/salty-water-?#!*/1/' => ['salty-water-?#!*', '1'],
            ],
        ];
        foreach ($items as $pattern => $item) {
            foreach ($item as $pathinfo => $result) {
                $res = Route::match($pathinfo, $pattern);
                $this->assertEquals($result, $res);
            }
        }

        $this->assertTrue(true);
    }

}