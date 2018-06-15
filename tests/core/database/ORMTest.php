<?php
/**
 * User: linzhv@qq.com
 * Date: 20/03/2018
 * Time: 10:54
 */
declare(strict_types=1);


namespace driphp\test\database;


use driphp\core\database\Dao;
use driphp\tests\UniTest;
use driphp\throws\core\database\RecordNotFoundException;
use driphp\throws\core\DatabaseException;

/**
 * Class ORMTest
 *
 * TODO
 *
 * @package driphp\test\database
 */
class ORMTest extends UniTest
{
    /**
     * @return UserORM
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     * @throws \driphp\throws\core\database\QueryException
     */
    public function testGetInstanceAndInstall()
    {
        $master = Dao::getInstance('master');
        $user = UserORM::getInstance(0, $master);
        $user->uninstall();
        $this->assertFalse($user->installed());
        try {
            $user->install();
        } catch (DatabaseException $exception) {
            dumpout($user->getLastSql(), $exception->getMessage(), $exception->getCode());
        }
        $this->assertTrue($user->installed());
        return $user;
    }

    /**
     * @depends testGetInstanceAndInstall
     * @param UserORM $user
     * @return UserORM
     * @throws
     */
    public function testInsert(UserORM $user)
    {
        $user->username = 'linzhv';
        $user->email = 'linzhv@qq.com';
        $this->assertTrue($user->insert(false));
        $this->assertEquals(
            'INSERT INTO `test_user` ( `username`,`email` ) VALUES ( ?,? );',
            $user->getLastSql());
        $this->assertArrayEqual(['linzhv', 'linzhv@qq.com'], $user->getLastParams());


        $user2 = UserORM::getInstance(0, $user->dao());
        $user2->username = 'linzhv2';
        $user2->email = 'linzhv2@qq.com';
        $this->assertTrue($user2->insert(true));

        return $user;
    }

    /**
     * @depends testInsert
     * @param UserORM $user
     * @return UserORM
     * @throws
     */
    public function testFind(UserORM $user)
    {
        # 刚插入的数据不会自动刷新
        $this->assertArrayEqual([
            'username' => 'linzhv',
            'email' => 'linzhv@qq.com',
        ], $user->data());
        # 没有强制刷新
        $this->assertArrayEqual([
            'username' => 'linzhv',
            'email' => 'linzhv@qq.com',
        ], $user->find()->data());
        # 强制刷新
        $this->assertTrue(count($user->find(true)->data()) > 2);


        $user = UserORM::getInstance(1234567, $user->dao());
        try {
            $user->find();
            $this->assertTrue(false);
        } catch (RecordNotFoundException $exception) {
            # id为1的记录是刚刚添加的
            $user = UserORM::getInstance(1, $user->dao());
            $user->find();
            $this->assertTrue('linzhv' === $user->username);
            $this->assertTrue('linzhv@qq.com' === $user->email);
        }
        return $user;
    }

    /**
     * @depends testFind
     * @param UserORM $user
     * @return UserORM
     * @throws RecordNotFoundException
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     * @throws \driphp\throws\core\database\QueryException
     * @throws \driphp\throws\core\database\RecordNotUniqueException
     */
    public function testUpdate(UserORM $user)
    {
        $user->username = 'lich4ung';
        $this->assertTrue($user->update());
        $user->find(false);
        $this->assertTrue('lich4ung' === $user->username);
        $user->find(true);
        $this->assertTrue('lich4ung' === $user->username);
        return $user;
    }

    /**
     * @depends testUpdate
     * @param UserORM $user
     * @return UserORM
     * @throws
     */
    public function testDelete(UserORM $user)
    {
        $this->assertTrue($user->delete());
        $this->assertTrue(empty($user->data()));
        try {
            UserORM::getInstance(1, $user->dao())->find(true);
            $this->assertTrue(false);
        } catch (RecordNotFoundException $exception) {
            $this->assertTrue(true);
        }
        return $user;
    }
}