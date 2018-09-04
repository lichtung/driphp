<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 04/09/2018
 * Time: 19:14
 */

namespace driphp\core\database;

/**
 * Class ORM 内置对象关系映射 (Object Relational Mapping)
 * @package driphp\core\database
 */
abstract class ORM
{
    /**
     * 数据表前缀
     * @return string
     */
    abstract public static function tablePrefix(): string;

    /**
     * 数据表名称
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * @return ORM
     */
    public static function findOne(): ORM
    {

    }

    /**
     * @return ORM[]
     */
    public static function findAll(): array
    {

    }

    public function distinct(){}
    public function fields(){}
    public function alias(){}
    public function join(){}
    public function innerJoin(){}
    public function rightJoin(){}
    public function where(){}
    public function whereOr(){}
    public function whereAnd(){}
    public function whereIn(){}
    public function whereNotIn(){}
    public function whereBetween(){}
    public function orderBy(){}
    public function groupBy(){}
    public function limit(){}
    public function having(){}


}