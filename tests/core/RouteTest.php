<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 13:49
 */
declare(strict_types=1);


namespace tests\core;


use PHPUnit\Framework\TestCase;
use driphp\core\Route;

class RouteTest extends TestCase
{

    public function testMatch()
    {
        $result = Route::match('/123/name/lisa','/{id}/name/{name}');
        dumpout($result);
    }

    /**
     * 执行匹配过程
     * @deprecated
     * @see RouteTest::testMatch()
     * @param string $pathInfo
     * @param string $pattern 正则/规则式
     * @return null|array 匹配成功返回数组，里面包含匹配出来的参数；不匹配时返回null
     */
    public static function oldMatch(string $pathInfo, string $pattern)
    {
        $positionBrace = strpos($pattern, '{');
        if ($positionBrace !== false) {
            $pattern = str_replace(
                ['{any}', '{num}', '/[any]', '/[num]'], # 花括号表示参数是必须要有的，中括号表示可选
                ['([^/]+)', '([0-9]+)', '(/[^/]+)?', '(/[0-9]+)?'], # 可选的会把前面的"/"一并带走
                $pattern);  //$pattern = preg_replace('/\[.+?\]/','([^/\[\]]+)',$pattern);//non-greediness mode
        } # dumpout($pattern);
        if (preg_match('#^' . $pattern . '$#', rtrim($pathInfo, '/'), $matches)) { # 使用 '#' 代替开头和结尾的 '/'，可以忽略 $pattern 中的 "/"
            array_shift($matches);
            array_walk($matches, function (&$item) {
                $item = ltrim($item, '/');
                is_numeric($item) and $item = (int)$item;
            });
        } else {
            $matches = null;
        }
        return $matches;
    }

    public function testOldMatch()
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
            foreach ($item as $pathInfo => $result) {
                $res = self::oldMatch($pathInfo, $pattern);
                $this->assertEquals($result, $res);
            }
        }

        $this->assertTrue(true);
    }

}