<?php
/**
 * User: linzhv@qq.com
 * Date: 20/03/2018
 * Time: 11:16
 */
declare(strict_types=1);


namespace sharin\test\database;

use sharin\core\database\ORM;
use sharin\core\database\Dao;

/**
 * Class UserORM
 *
 * @property string $username   账号
 * @property string $password   密码,默认为123456的md5+sha1加密后的值
 * @property string $email      电子邮件
 * @property string $nickname   昵称
 * @property string $avatar     头像
 * @property string $disable    禁用情况,0-禁用 非0-启用
 * @property string $update_time    资料修改时间
 * @property string $register_time  注册时间
 * @property string $meta       附加信息
 * @property string $home_page home页面
 * @property int $id
 *
 * @method UserORM getInstance($primaryKey = 0, Dao $dao = null) static
 *
 * @package dripex\test\database
 */
class UserORM extends ORM
{
    protected $primaryKey = 'id';
    protected $tableName = 'user';
    protected $tablePrefix = 'test_';

    protected $uniqueKeys = [
        [
            'username', 'email'
        ],
    ];
    protected $tableFields = [
        'id' => [
            'type' => 'int(10) unsigned',
            'notnull' => true,
            'autoinc' => true,
            'comment' => '自增ID',
        ],
        'username' => [
            'type' => 'varchar(255)',
            'notnull' => true,
            'comment' => '账号名称',
            'index' => true,# IndexKey
            'unique' => true,# UniqueKey
        ],
        'email' => [
            'type' => 'varchar(255)',
            'notnull' => true,
            'default' => '',
            'index' => true,# IndexKey
            'unique' => true,# UniqueKey
        ],
        'password' => [
            'type' => 'char(32)',
            'notnull' => true,
            'comment' => '账号名称',
            'default' => 'd93a5def7511da3d0f2d171d9c344e91',
        ],
        'add_time' => [
            'type' => 'timestamp',
            'default' => 'CURRENT_TIMESTAMP',
        ],
        'update_time' => [
            'type' => 'timestamp',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
    ];

}