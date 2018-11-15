<?php
/**
 * User: linzhv@qq.com
 * Date: 20/03/2018
 * Time: 10:54
 */
declare(strict_types=1);


namespace driphp\test\database;

use driphp\database\Dao;
use driphp\tests\database\orm\UserORM;
use driphp\tests\UnitTest;
use driphp\throws\database\exec\DuplicateException;
use driphp\throws\database\NotFoundException;

/**
 * Class ORMTest
 *
 * @package driphp\test\database
 */
class ORMTest extends UnitTest
{
    /**
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\ExecuteException
     * @throws \driphp\throws\database\QueryException
     */
    public function testInstall()
    {
        $user = new UserORM(Dao::connect('right'));
        $user->uninstall();
        $this->assertTrue($user->installed() === false);
        $user->install();
        $this->assertTrue($user->installed() === true);
        $this->assertTrue(in_array($user->getTableName(), $user->dao()->getTables()));
    }

    /**
     * @throws DuplicateException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\ExecuteException
     * @throws \driphp\throws\database\NotFoundException
     * @throws \driphp\throws\database\QueryException
     */
    public function testInsertAndFind()
    {
        /** @var UserORM $user1 */
        $user1 = new UserORM(Dao::connect('right'));
        /** @var UserORM $user2 */
        $user2 = $user1->insert([
            'username' => 'linzhv',
            'email' => 'linzhv@outlook.com',
        ]);
        $this->assertTrue([] === $user1->toArray());
        $user1->username = '784855684';
        $user1->email = '784855684@qq.com';
        # 新值会被设置 旧值仍然为空
        $user4 = $user1->insert();
        # 新值会被设置 旧值仍然为空
        $this->assertTrue(['username' => '784855684', 'email' => '784855684@qq.com'] === $user1->getNewValues());
        $this->assertTrue($user1->toArray() === $user1->getNewValues());
        $this->assertTrue([] === $user1->getOldValues());

        /** @var UserORM $user3 */
        $user3 = $user1->find(1);

        $this->assertTrue(0 === intval($user1->id));
        $this->assertTrue(1 === intval($user2->id));
        $this->assertTrue(1 === intval($user3->id));

        $this->assertTrue($user2->toArray() === $user3->toArray());
        $this->assertTrue($user2->username === 'linzhv');

        $this->assertTrue($user1 !== $user2);
        $this->assertTrue($user2 !== $user3);
        $this->assertTrue($user3 !== $user4);
        $this->assertTrue($user1 !== $user3);
        $this->assertTrue($user1 !== $user4);
        # 插入重复数据抛出DuplicateException
        try {
            $user1->insert([
                'username' => 'linzhv',
                'email' => 'linzhv@outlook.com',
            ]);
            $this->assertTrue(false);
        } catch (DuplicateException $exception) {
            $this->assertTrue(true);
        }
    }

    /**
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\QueryException
     */
    public function testSelectAndCount()
    {
        /** @var UserORM $user1 */
        $user1 = new UserORM(Dao::connect('right'));

        $this->assertTrue(2 === $user1->query()->count());
        $this->assertTrue(1 === $user1->query()->where(['email' => 'linzhv@outlook.com'])->count());
        $this->assertTrue(1 === $user1->query()->where(['email' => '784855684@qq.com'])->count());
        $this->assertTrue(0 === $user1->query()->where(['email' => 'no-user@qq.com'])->count());

        /** @var UserORM[] $list */
        $list = $user1->query()->fetchAll();
        $this->assertTrue(2 === count($list));
        $outlook = array_shift($list);
        $this->assertTrue('linzhv@outlook.com' === $outlook->email);
        $qq = array_shift($list);
        $this->assertTrue('784855684@qq.com' === $qq->email);
    }

    /**
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\ExecuteException
     * @throws \driphp\throws\database\NotFoundException
     * @throws \driphp\throws\database\QueryException
     */
    public function testUpdate()
    {
        /** @var UserORM $user1 */
        $user1 = new UserORM(Dao::connect('right'));
        /** @var UserORM $user2 */
        $user2 = $user1->query()->where(['email' => 'linzhv@outlook.com'])->fetch();
        $this->assertTrue('linzhv' === $user2->username);
        $user2->username = 'linzh';
        $this->assertTrue(['username' => 'linzh'] === $user2->getNewValues());
        $this->assertTrue(['username' => 'linzhv'] === $user2->getOldValues());
        $oldUpdatedAt = $user2->updated_at;
        sleep(1);
        $user2->update();
        $this->assertTrue($user2->updated_at !== $oldUpdatedAt);
        $this->assertTrue($user2->username === 'linzh');
    }

    /**
     * @throws NotFoundException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\ExecuteException
     * @throws \driphp\throws\database\QueryException
     */
    public function testSoftDelete()
    {
        /** @var UserORM $user1 */
        $user1 = new UserORM(Dao::connect('right'));

        $user2 = $user1->find(1);
        $this->assertTrue($user2->delete());
        try {
            $user1->find(1);
            $this->wrongHere();
        } catch (NotFoundException $exception) {
            $this->rightHere();
        }

        $list = $user1->dao()->query("select * from {$user1->getTableName()} ;");
        $this->assertTrue(2 === count($list));
        $this->assertTrue($list[0]['deleted_at'] !== null);
        $this->assertTrue($list[1]['deleted_at'] === null);
    }

    public function testHardDelete()
    {
        /** @var UserORM $user1 */
        $user1 = new UserORM(Dao::connect('right'));
        $user2 = $user1->find(2);
        $this->assertTrue($user2->hardDelete());
        try {
            $user1->find(2);
            $this->wrongHere();
        } catch (NotFoundException $exception) {
            $this->rightHere();
        }
        $list = $user1->dao()->query("select * from {$user1->getTableName()} ;");
        $this->assertTrue(1 === count($list));
        $this->assertTrue($list[0]['deleted_at'] !== null);
    }
//    /**
//     * @return UserORM
//     * @throws \driphp\throws\core\ClassNotFoundException
//     * @throws \driphp\throws\core\DriverNotDefinedException
//     * @throws \driphp\throws\database\ConnectException
//     * @throws \driphp\throws\database\ExecuteException
//     * @throws \driphp\throws\database\QueryException
//     */
//    public function testGetInstanceAndInstall()
//    {
//        $master = Dao::getInstance('master');
//        $user = UserORM::getInstance(0, $master);
//        $user->uninstall();
//        $this->assertFalse($user->installed());
//        try {
//            $user->install();
//        } catch (DatabaseException $exception) {
//            dumpout($user->getLastSql(), $exception->getMessage(), $exception->getCode());
//        }
//        $this->assertTrue($user->installed());
//        return $user;
//    }
//
//    /**
//     * @depends testGetInstanceAndInstall
//     * @param UserORM $user
//     * @return UserORM
//     * @throws
//     */
//    public function testInsert(UserORM $user)
//    {
//        $user->username = 'linzhv';
//        $user->email = 'linzhv@qq.com';
//        $this->assertTrue($user->insert(false));
//        $this->assertEquals(
//            'INSERT INTO `test_user` ( `username`,`email` ) VALUES ( ?,? );',
//            $user->getLastSql());
//        $this->assertArrayEqual(['linzhv', 'linzhv@qq.com'], $user->getLastParams());
//
//
//        $user2 = UserORM::getInstance(0, $user->dao());
//        $user2->username = 'linzhv2';
//        $user2->email = 'linzhv2@qq.com';
//        $this->assertTrue($user2->insert(true));
//
//        return $user;
//    }
//
//    /**
//     * @depends testInsert
//     * @param UserORM $user
//     * @return UserORM
//     * @throws
//     */
//    public function testFind(UserORM $user)
//    {
//        # 刚插入的数据不会自动刷新
//        $this->assertArrayEqual([
//            'username' => 'linzhv',
//            'email' => 'linzhv@qq.com',
//        ], $user->data());
//        # 没有强制刷新
//        $this->assertArrayEqual([
//            'username' => 'linzhv',
//            'email' => 'linzhv@qq.com',
//        ], $user->find()->data());
//        # 强制刷新
//        $this->assertTrue(count($user->find(true)->data()) > 2);
//
//
//        $user = UserORM::getInstance(1234567, $user->dao());
//        try {
//            $user->find();
//            $this->assertTrue(false);
//        } catch (RecordNotFoundException $exception) {
//            # id为1的记录是刚刚添加的
//            $user = UserORM::getInstance(1, $user->dao());
//            $user->find();
//            $this->assertTrue('linzhv' === $user->username);
//            $this->assertTrue('linzhv@qq.com' === $user->email);
//        }
//        return $user;
//    }
//
//    /**
//     * @depends testFind
//     * @param UserORM $user
//     * @return UserORM
//     * @throws RecordNotFoundException
//     * @throws \driphp\throws\core\ClassNotFoundException
//     * @throws \driphp\throws\core\DriverNotDefinedException
//     * @throws \driphp\throws\database\ConnectException
//     * @throws \driphp\throws\database\ExecuteException
//     * @throws \driphp\throws\database\QueryException
//     * @throws \driphp\throws\database\RecordNotUniqueException
//     */
//    public function testUpdate(UserORM $user)
//    {
//        $user->username = 'lich4ung';
//        $this->assertTrue($user->update());
//        $user->find(false);
//        $this->assertTrue('lich4ung' === $user->username);
//        $user->find(true);
//        $this->assertTrue('lich4ung' === $user->username);
//        return $user;
//    }
//
//    /**
//     * @depends testUpdate
//     * @param UserORM $user
//     * @return UserORM
//     * @throws
//     */
//    public function testDelete(UserORM $user)
//    {
//        $this->assertTrue($user->delete());
//        $this->assertTrue(empty($user->data()));
//        try {
//            UserORM::getInstance(1, $user->dao())->find(true);
//            $this->assertTrue(false);
//        } catch (RecordNotFoundException $exception) {
//            $this->assertTrue(true);
//        }
//        return $user;
//    }
}