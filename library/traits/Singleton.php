<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 18:47
 */
declare(strict_types=1);


namespace sharin\library\traits;

/**
 * Trait Singleton 单例模式
 * @package sharin\library\traits
 */
trait Singleton
{

    /**
     * @param array ...$params
     * @return mixed
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