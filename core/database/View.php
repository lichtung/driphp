<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/29 0029
 * Time: 14:26
 */

namespace driphp\core\database;


abstract class View
{
    /**
     * 返回视图定义语句
     * @return string
     */
    abstract public function definition(): string;

}