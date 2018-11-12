<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:16
 */

namespace driphp\library\traits;


trait Factory
{

    /**
     * @param mixed ...$arguments
     * @return mixed
     * @throws \ReflectionException
     */
    public static function factory(...$arguments)
    {
        static $_multiple_instances = [];
        $static = static::class;
        isset($_multiple_instances[$static]) or $_multiple_instances[$static] = [];
        if ($arguments) {
            $index = md5(serialize($arguments));
        } else {
            $index = '';
        }

        if (!isset($_multiple_instances[$static][$index])) {
            $_multiple_instances[$static][$index] = $arguments ?
                (new \ReflectionClass($static))->newInstanceArgs($arguments) : new $static();
        }
        return $_multiple_instances[$static][$index];
    }
}