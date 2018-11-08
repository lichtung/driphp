<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 14:45
 */

namespace driphp\library\client;

use stdClass;

class Mongo
{

    /**
     * executeQuery返回的浮标遍历时的数据
     * @see \MongoDB\Driver\Manager::executeQuery
     * @param stdClass|array|int|string $obj
     * @param int $_level
     * @return array|int|string
     */
    public static function object2array($obj, int $_level = 0)
    {
        if ($_level > 500) return (array)$obj; # 过度嵌套,直接返回
        if (is_object($obj)) { # 转array
            $obj = (array)$obj;
            foreach ($obj as $key => $val) {
                $obj[$key] = self::object2array($val, $_level + 1);
            }
        } elseif (is_array($obj)) { # 遍历,每个元素转array
            $tmp = [];
            foreach ($obj as $item) {
                $tmp[] = self::object2array($item, $_level + 1);
            }
            $obj = $tmp;
        }# 其他类型完整返回
        return $obj;
    }

}