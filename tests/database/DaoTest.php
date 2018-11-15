<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 12:29
 */
declare(strict_types=1);


namespace tests\core\database;

use driphp\database\Dao;
use driphp\tests\UnitTest;
use driphp\throws\database\ConnectException;
use driphp\throws\database\ExecuteException;
use driphp\throws\DriverNotFoundException;

/**
 * Class DaoTest DAO基本测试
 * @package tests\core\database
 */
class DaoTest extends UnitTest
{
    /**
     * @throws ConnectException
     * @throws \driphp\throws\ClassNotFoundException
     */
    public function testDriverNotFoundException()
    {
        $config = $this->config(Dao::class);
        $this->assertTrue(isset($config['drivers']['right'], $config['drivers']['wrong']));

        # 测试驱动不存在异常
        try {
            # 嗲用drive时进行连接
            Dao::connect(); # default不存在
            $this->assertTrue(false);
        } catch (DriverNotFoundException $exception) {
            $this->assertEquals("driver 'default' not found", $exception->getMessage());
        }
    }

    /**
     * @throws ConnectException
     * @throws DriverNotFoundException
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     */
    public function testDatabaseFindAndCreate()
    {
        $dao = Dao::connect('server');
        $databases = $dao->getDatabases();
        # 数据库不存在
        $this->assertTrue(in_array('test', $databases) === false);
        # 创建数据库
        $result = $dao->exec('CREATE SCHEMA `test` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;');
        $this->assertTrue(1 === $result); # mysql workbench 执行创建数据库操作返回 "1 row(s) affected"
        $this->assertTrue(true);
    }

    /**
     * @throws ConnectException
     * @throws DriverNotFoundException
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     */
    public function testDatabaseDelete()
    {
        $dao = Dao::connect('server');
        $result = $dao->exec('DROP DATABASE `test`;');
        $this->assertTrue(0 === $result);  # mysql workbench 执行数据库删除操作返回 "0 row(s) affected"
    }

}