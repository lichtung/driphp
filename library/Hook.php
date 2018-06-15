<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 16:24
 */

namespace driphp\library;

/**
 * Class Hook
 * @package driphp\library
 * @deprecated
 */
class Hook
{

    /**
     * 设置钩子规则
     * @param array $hook
     * @return void
     */
    public static function setHookMap(array $hook)
    {
        self::$_hook = array_merge(self::$_hook, $hook);
    }

    /**
     * 设置钩子
     * @param string $tag
     * @param callable $behavior
     * @return void
     */
    public static function setHook(string $tag, callable $behavior)
    {
        if (!isset(self::$_hook[$tag])) self::$_hook[$tag] = [];
        self::$_hook[$tag][] = $behavior;
    }

    /**
     * 调用钩子
     * @param string $tag
     * @param $params
     * @return void
     */
    public static function hook(string $tag, $params)
    {
        static $_behaviours = [];
        if (!empty(self::$_hook[$tag])) foreach (self::$_hook[$tag] as $name) {
            if (is_callable($name)) {
                //如果是闭包，则直接执行闭包函数
                $res = $name($params);
            } elseif (is_string($name)) {
                # 如果是类名称,则认为是一个行为类,调用其实例的run方法
                isset($_behaviours[$name]) or $_behaviours[$name] = new $name();
                if (!is_callable([$_behaviours[$name], $tag])) $tag = 'run';//tag默认是方法名称
                $res = call_user_func_array([$_behaviours[$name], $tag], [$params, $tag]);
            } else {
                $res = true;
            }
            if (false === $res) break; // 如果返回false 则中断行为执行
        }
    }


    /**
     * 钩子集合
     * key可能是某种有意义的方法，也可能是一个标识符
     * value可能是闭包函数或者类名称
     * 如果是value类名称，则key可能是其调用的方法的名称（此时会检查这个类中是否存在这个方法），也可能是一个标识符
     * @var array
     */
    private static $_hook = [];
}