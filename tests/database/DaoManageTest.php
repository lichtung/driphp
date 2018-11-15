<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 14:51
 */

namespace tests\core\database;


use driphp\database\Dao;
use driphp\tests\UnitTest;
use driphp\throws\database\ConnectException;
use driphp\throws\database\ExecuteException;
use driphp\throws\database\GeneralException;
use driphp\throws\database\QueryException;

/**
 * Class DaoManageTest DAO数据操作测试
 * @package tests\core\database
 */
class DaoManageTest extends UnitTest
{
    /**
     * @throws ConnectException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ExecuteException
     */
    public function testInit()
    {

        $dao = Dao::connect('server');
        $databases = $dao->getDatabases();
        if (!in_array('test', $databases)) {
            $dao->exec('CREATE SCHEMA `test` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;');
        }
        $this->assertTrue(true);
    }

    /**
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     */
    public function testConnection()
    {
        try {
            Dao::connect('wrong');
            $this->assertTrue(false);
        } catch (ConnectException $throwable) {
            $this->assertTrue(true);
            $this->assertTrue(1049 === $throwable->getCode()); # Unknown database 'test'
        }

        try {
            Dao::connect('wrong_user');
        } catch (ConnectException $throwable) {
            $this->assertTrue(true);
            $this->assertTrue(1045 === $throwable->getCode()); # SQLSTATE[HY000] [1045] Access denied for user 'username'@'localhost' (using password: YES)
        }

        $dao = null;
        try {
            $dao = Dao::connect('right');
            $this->assertTrue(true);
        } catch (ConnectException $throwable) {
            $this->assertTrue(false);
        }
        return $dao;
    }

    /**
     * @depends testConnection
     * @param Dao $dao
     * @return Dao
     * @throws
     */
    public function testExec($dao)
    {
        $name = random_int(10000, 99999);
        $sql_drop_table = 'DROP TABLE IF EXISTS `tba`;';
        $sql_create_table = 'CREATE TABLE `tba` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        $sql_insert = 'Insert into tba (`name`) VALUE (:name),(:name2);';

        # 表的创建和删除实际影响的记录数目为0
        $this->assertTrue($dao->exec($sql_drop_table) === 0);
        $this->assertTrue($dao->exec($sql_create_table) === 0);
        $this->assertTrue(2 === $dao->exec($sql_insert, [':name' => $name, ':name2' => $name . 'hello_world'])); # 一次插入2条

        # bad execute without bind
        try {
            $dao->exec('insert into tba (`field_not_found`) VALUE (\'hello world\')');
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Column not found') !== false);
        }

        try {
            $dao->exec('insert into table_not_found (`name`) VALUE (\'hello world\')');
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Base table or view not found') !== false);
        }

        # bad execute with bind
        try {
            $dao->exec('insert into tba (`field_not_found`) VALUE (:name)', [':name' => 'hello world']);
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Column not found') !== false);
        }
        try {
            $dao->exec('insert into table_not_found (`name`) VALUE (:name)', [':name' => 'hello world']);
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Base table or view not found') !== false);
        }

        return $dao;
    }

    /**
     * @depends testExec
     * @param Dao $dao
     * @return Dao
     * @throws
     */
    public function testQuery($dao)
    {

        $list = $dao->query('select id,name from tba;');
        $this->assertTrue(2 === count($list));
        $this->assertTrue((int)$list[0]['id'] === 1);
        $this->assertTrue((int)$list[1]['id'] === 2);

        $list2 = $dao->query('select * from tba where `name` like :name;', [':name' => '%hello_world']);
        $this->assertTrue((int)$list2[0]['id'] === 2);

        # bad query without bind
        try {
            $dao->query('select * from table_not_found;');
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Base table or view not found') !== false);
        }
        try {
            $dao->query('select field_not_found from tba;');
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Column not found') !== false);
        }

        # bad query with bind
        try {
            $dao->query('select * from table_not_found where name like :name;', [':name' => '%hello_world']);
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Base table or view not found') !== false);
        }
        try {
            $dao->query('select field_not_found from tba where name like :name;', [':name' => '%hello_world']);
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(strpos($t->getMessage(), 'Column not found') !== false);
        }

        return $dao;
    }

    /**
     * @depends testQuery
     * @param Dao $dao
     * @return Dao
     * @throws
     */
    public function testDelete($dao)
    {
        $this->assertTrue(1 === $dao->exec('delete from tba where `name` like :name;', [':name' => '%hello_world']));
        return $dao;
    }

    /**
     * @depends testDelete
     * @param Dao $dao
     * @return void
     * @throws
     */
    public function testTransaction($dao)
    {
        $dao->beginTransaction();
        $this->assertTrue(1 === $dao->exec('delete from tba ;'));
        $this->assertTrue(0 === count($dao->query('select 1 from tba;')));
        $dao->rollback();
        $this->assertTrue(1 === count($dao->query('select 1 from tba;')));

        $dao->beginTransaction();
        $this->assertTrue(1 === $dao->exec('delete from tba ;'));
        $this->assertTrue(0 === count($dao->query('select 1 from tba;')));
        $dao->commit();
        $this->assertTrue(0 === count($dao->query('select 1 from tba;')));

        # nested transaction
        $dao->beginTransaction();

        $sql_insert = 'insert into tba (`name`) VALUE (:name),(:name2);';
        $this->assertTrue(2 === $dao->exec($sql_insert, [':name' => 'A', ':name2' => 'B'])); # 一次插入2条
        $dao->exec('delete from tba limit 1;');
        try {
            $dao->beginTransaction();
            $this->assertTrue(false);
        } catch (GeneralException $exception) {
            $this->assertTrue($exception->getMessage() === 'There is already an active transaction');
        }
        # 没有commit,数据表中最终是没有数据的
    }


}