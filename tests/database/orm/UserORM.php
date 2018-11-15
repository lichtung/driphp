<?php
/**
 * User: linzhv@qq.com
 * Date: 20/03/2018
 * Time: 11:16
 */
declare(strict_types=1);


namespace driphp\tests\database\orm;

use driphp\database\ORM;

/**
 * Class UserORM 用户ORM测试
 *
 * @property int $id
 * @property string $username   账号
 * @property string $password   密码,默认为123456的md5+sha1加密后的值
 * @property string $email      电子邮件
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @package dripex\test\database
 */
class UserORM extends ORM
{

    public function tablePrefix(): string
    {
        return 'test_';
    }

    public function tableName(): string
    {
        return 'user';
    }

    public function structure(): array
    {
        return [
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
        ];
    }


}