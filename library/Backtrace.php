<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 16:22
 */

namespace driphp\library;

/**
 * Class Backtrace
 * @package driphp\library
 * @deprecated
 */
class Backtrace
{
    /**
     * 调用位置
     */
    const PLACE_BACKWARD = 0; //表示调用者自身的位置
    const PLACE_SELF = 1;// 表示调用调用者的位置
    const PLACE_FORWARD = 2;
    const PLACE_FURTHER_FORWARD = 3;
    /**
     * backwork
     * 信息组成
     */
    const ELEMENT_FUNCTION = 1;
    const ELEMENT_FILE = 2;
    const ELEMENT_LINE = 4;
    const ELEMENT_CLASS = 8;
    const ELEMENT_TYPE = 16;
    const ELEMENT_ARGS = 32;
    const ELEMENT_ALL = 0;

    /**
     * 返回调用当前方法(Backtrace::getPreviousMethod()所在的位置)的函数的前一个方法
     * @return string
     */
    public static function getPreviousMethod()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        if (isset($trace[self::PLACE_FORWARD])) {
            return $trace[self::PLACE_FORWARD]['function']??'';
        }
        return '';
    }

    /**
     * 获取调用者本身的位置
     * @param int $elements 为0是表示获取全部信息
     * @param int $place 位置属性
     * @return array|string
     */
    public static function backtrace(int $elements = self::ELEMENT_ALL, int $place = self::PLACE_SELF)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        if ($elements) {
            $result = [];
            $elements & self::ELEMENT_ARGS and $result[self::ELEMENT_ARGS] = $trace[$place]['args']??'';
            $elements & self::ELEMENT_CLASS and $result[self::ELEMENT_CLASS] = $trace[$place]['class'] ?? '';
            $elements & self::ELEMENT_FILE and $result[self::ELEMENT_FILE] = $trace[$place]['file'] ?? '';
            $elements & self::ELEMENT_FUNCTION and $result[self::ELEMENT_FUNCTION] = $trace[$place]['function'] ?? '';
            $elements & self::ELEMENT_LINE and $result[self::ELEMENT_LINE] = $trace[$place]['line'] ?? '';
            $elements & self::ELEMENT_TYPE and $result[self::ELEMENT_TYPE] = $trace[$place]['type'] ?? '';
            1 === count($result) and $result = array_shift($result);//一个结果直接返回
        } else {
            $result = $trace[$place];
        }
        return $result;
    }
}