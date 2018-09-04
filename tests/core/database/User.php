<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 04/09/2018
 * Time: 19:16
 */

namespace tests\core\database;


use driphp\core\database\ORM;

/**
 * Class User
 * @method User findOne() static
 * @package tests\core\database
 */
class User extends ORM
{

    public static function tableName(): string
    {
        return 'user';
    }

    public static function tablePrefix(): string
    {
        return '';
    }

}