<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:15
 */

namespace driphp\library\traits;

/**
 * Trait Singleton 单例模式
 * @package driphp\library\traits
 */
trait Singleton
{
    /**
     * @param mixed ...$params
     * @return mixed
     * @throws \ReflectionException
     */
    public static function getInstance(...$params)
    {
        static $_instances = [];
        $className = static::class;
        $key = $params ? md5($className . '###' . serialize($params)) : '_default_';
        isset($_instances[$key]) or $_instances[$key] = $params ? (new \ReflectionClass($className))->newInstanceArgs($params) : new $className();
        return $_instances[$key];
    }

}