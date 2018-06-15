<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 12:29
 */
declare(strict_types=1);


namespace tests\core\database;

use driphp\throws\core\database\GeneralException;
use driphp\throws\core\database\QueryException;
use driphp\core\database\Dao;
use driphp\core\database\driver\MySQL;
use driphp\tests\UniTest;
use driphp\throws\core\database\ConnectException;
use driphp\throws\core\database\ExecuteException;
use driphp\throws\core\DriverNotDefinedException;

class DaoTest extends UniTest
{
    /**
     * @return array
     * @throws
     */
    public function testGetInstance()
    {
        $daoConfig = $this->config(Dao::class);
        $this->assertTrue(isset($daoConfig['drivers']['master'], $daoConfig['drivers']['slave']));

        # 测试连接错误
        # 创建dao对象时并不立即连接对象
        $dao = Dao::getInstance('driver_not_found');
        try {
            # 嗲用drive时进行连接
            $dao->drive();
            $this->assertTrue(false);
        } catch (DriverNotDefinedException $exception) {
            $this->assertEquals("driver 'driver_not_found' not found", $exception->getMessage());
        }
        $dao = Dao::getInstance();
        $master = Dao::getInstance('master');
        $slave = Dao::getInstance('slave');
        $wrong_user = Dao::getInstance('wrong_user');
        $wrong_dbname = Dao::getInstance('wrong_dbname');

        $this->assertTrue('master' === $master->getIndex());
        $this->assertTrue('slave' === $slave->getIndex());
        $this->assertTrue('wrong_user' === $wrong_user->getIndex());
        $this->assertTrue('wrong_dbname' === $wrong_dbname->getIndex());
        $this->assertTrue('default' === $dao->getIndex());


        $dao->drive();
        $master->drive();
        $slave->drive();
        try {
            $wrong_dbname->drive();
            $this->assertTrue(false);
        } catch (ConnectException $exception) {
            $this->assertTrue(MySQL::ERROR_DATABASE_NOT_FOUND === $exception->getCode());
        }
        try {
            $wrong_user->drive();
            $this->assertTrue(false);
        } catch (ConnectException $exception) {
            $this->assertTrue(MySQL::ERROR_USER_PASSWD_INVALID === $exception->getCode());
        }


        $this->assertTrue(MySQL::class === $master->getDriverName());
        $this->assertTrue(MySQL::class === $slave->getDriverName());
        $this->assertTrue(MySQL::class === $wrong_user->getDriverName());
        $this->assertTrue(MySQL::class === $wrong_dbname->getDriverName());
        $this->assertTrue(MySQL::class === $dao->getDriverName());

        $this->assertArrayEqual($daoConfig['drivers']['master']['config'], $master->getDriverConfig());
        $this->assertArrayEqual($daoConfig['drivers']['slave']['config'], $slave->getDriverConfig());
        $this->assertArrayEqual($daoConfig['drivers']['wrong_user']['config'], $wrong_user->getDriverConfig());
        $this->assertArrayEqual($daoConfig['drivers']['wrong_dbname']['config'], $wrong_dbname->getDriverConfig());
        $this->assertArrayEqual($daoConfig['drivers']['default']['config'], $dao->getDriverConfig());

        return [
            $master,
            $slave,
        ];
    }


    /**
     * @depends testGetInstance
     * @param Dao[] $daoes
     * @return Dao[]
     * @throws
     */
    public function testExec(array $daoes)
    {
        list($master, $slave) = $daoes;
        $name = random_int(10000, 99999);
        $sql_drop_table = 'DROP TABLE IF EXISTS `tba`;';
        $sql_create_table = 'CREATE TABLE `tba` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        $sql_insert = 'Insert into tba (`name`) VALUE (:name),(:name2);';

        # 表的创建和删除实际影响的记录数目为0
        $this->assertTrue($master->exec($sql_drop_table) === 0);
        $this->assertTrue($master->exec($sql_create_table) === 0);
        $this->assertTrue($slave->exec($sql_drop_table) === 0);
        $this->assertTrue($slave->exec($sql_create_table) === 0);

        $this->assertTrue(2 === $master->exec($sql_insert, [':name' => $name, ':name2' => $name . 'hello_world']));
        $this->assertTrue(2 === $slave->exec($sql_insert, [':name' => $name, ':name2' => $name . 'hello_world']));

        # bad execute without bind
        try {
            $master->exec('insert into tba (`field_not_found`) VALUE (\'hello world\')');
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(MySQL::ERROR_FIELD_NOT_FOUND === $t->getCode());
        }
        try {
            $master->exec('insert into table_not_found (`name`) VALUE (\'hello world\')');
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(MySQL::ERROR_TABLE_NOT_FOUND === $t->getCode());
        }
        # bad execute with bind
        try {
            $master->exec('insert into tba (`field_not_found`) VALUE (:name)', [':name' => 'hello world']);
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(MySQL::ERROR_FIELD_NOT_FOUND === $t->getCode());
        }
        try {
            $master->exec('insert into table_not_found (`name`) VALUE (:name)', [':name' => 'hello world']);
            $this->assertTrue(false);
        } catch (ExecuteException $t) {
            $this->assertTrue(MySQL::ERROR_TABLE_NOT_FOUND === $t->getCode());
        }

        return $daoes;
    }

    /**
     * @depends testExec
     * @param Dao[] $daoes
     * @return Dao[]
     * @throws
     */
    public function testQuery(array $daoes)
    {
        list($master,) = $daoes;

        $list = $master->query('select id,name from tba;');
        $this->assertTrue(2 === count($list));
        $this->assertTrue((int)$list[0]['id'] === 1);
        $this->assertTrue((int)$list[1]['id'] === 2);

        $list2 = $master->query('select * from tba where `name` like :name;', [':name' => '%hello_world']);
        $this->assertTrue((int)$list2[0]['id'] === 2);

        # bad query without bind
        try {
            $master->query('select * from table_not_found;');
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(MySQL::ERROR_TABLE_NOT_FOUND === $t->getCode());
        }
        try {
            $master->query('select field_not_found from tba;');
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(MySQL::ERROR_FIELD_NOT_FOUND === $t->getCode());
        }
        # bad query with bind
        try {
            $master->query('select * from table_not_found where name like :name;', [':name' => '%hello_world']);
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(MySQL::ERROR_TABLE_NOT_FOUND === $t->getCode());
        }
        try {
            $master->query('select field_not_found from tba where name like :name;', [':name' => '%hello_world']);
            $this->assertTrue(false);
        } catch (QueryException $t) {
            $this->assertTrue(MySQL::ERROR_FIELD_NOT_FOUND === $t->getCode());
        }

        return $daoes;
    }

    /**
     * @depends testQuery
     * @param Dao[] $daoes
     * @return Dao[]
     * @throws
     */
    public function testDelete(array $daoes)
    {
        list($master,) = $daoes;
        $this->assertTrue(1 === $master->exec('delete from tba where `name` like :name;', [':name' => '%hello_world']));
        return $daoes;
    }

    /**
     * @depends testDelete
     * @param Dao[] $daoes
     * @return void
     * @throws
     */
    public function testTransaction(array $daoes)
    {
        list($master, $slave) = $daoes;
        $master->beginTransaction();
        $this->assertTrue(1 === $master->exec('delete from tba ;'));
        $this->assertTrue(0 === count($master->query('select 1 from tba;')));
        $master->rollback();
        $this->assertTrue(1 === count($master->query('select 1 from tba;')));

        $master->beginTransaction();
        $this->assertTrue(1 === $master->exec('delete from tba ;'));
        $this->assertTrue(0 === count($master->query('select 1 from tba;')));
        $master->commit();
        $this->assertTrue(0 === count($master->query('select 1 from tba;')));

        # nested transaction
        $slave->beginTransaction();
        $this->assertTrue(2 === count($slave->query('select 1 from tba;')));
        $slave->exec('delete from tba limit 1;');
        try {
            $slave->beginTransaction();
            $this->assertTrue(false);
        } catch (GeneralException $exception) {
            $this->assertTrue(Dao::ERROR_TRANSACTION_ALREADY_ACTIVE === $exception->getCode());
        }


    }

}