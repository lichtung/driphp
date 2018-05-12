<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 18:44
 */
declare(strict_types=1);


namespace sharin\library\traits;
/**
 * Trait Multiton 多例模式
 * @package sharin\library\traits
 */
trait Multiton
{
    /**
     * @var array
     */
    protected static $_multiple_instances = [];

    /**
     * @param array ...$arguments
     * @return mixed
     */
    public static function getInstanceBy(...$arguments)
    {
        $static = static::class;
        isset(self::$_multiple_instances[$static]) or self::$_multiple_instances[$static] = [];

        if ($arguments) {
            $arguments = func_get_args();
            $index = md5(serialize($arguments));
        } else {
            $index = '';
        }

        if (!isset(self::$_multiple_instances[$static][$index])) {
            self::$_multiple_instances[$static][$index] = $arguments ?
                (new \ReflectionClass($static))->newInstanceArgs($arguments) :
                new $static();
        }
        return self::$_multiple_instances[$static][$index];
    }
}