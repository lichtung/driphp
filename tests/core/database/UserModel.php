<?php
/**
 * User: linzhv@qq.com
 * Date: 21/03/2018
 * Time: 12:21
 */
declare(strict_types=1);


namespace sharin\test\database;

use sharin\core\database\Dao;
use sharin\core\database\Model;

/**
 * Class UserModel
 *
 * @method UserModel getInstance(Dao $dao = null) static
 *
 * @package dripex\test\database
 */
class UserModel extends Model
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