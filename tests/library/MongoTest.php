<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 11:25
 */

namespace driphp\test\library;


use driphp\core\Chars;
use driphp\Kernel;
use driphp\library\client\Mongo;
use driphp\library\client\mongo\NotFoundException;
use driphp\library\client\mongo\IterateHandlerInterface;
use driphp\tests\UnitTest;

class MongoTest extends UnitTest
{
    /**
     * 测试获取实例
     * @return Mongo
     */
    public function testInstance()
    {
        # 获取修改后的配置
        $config = Kernel::getInstance()->config(Mongo::class);
        $config['password'] = 'a123456';
        $mongo1 = Mongo::factory($config);
        $this->assertArrayEqual($mongo1->config(), [
            'user' => 'linzh',
            'password' => 'a123456',
            'host' => '192.168.200.100',
            'port' => 27017,
            'database' => 'default',
            'collection' => 'default',
            'timeout' => 10000,
        ]);
        # 从初始配置中获取
        $mongo = Mongo::factory();
        $this->assertArrayEqual($mongo->config(), [
            'user' => 'linzh',
            'password' => 'a1236547890',
            'host' => '192.168.200.100',
            'port' => 27017,
            'database' => 'default',
            'collection' => 'default',
            'timeout' => 10000,
        ]);
        return $mongo;
    }

    /**
     * @depends testInstance
     * @param Mongo $mongo
     * @return Mongo
     * @throws \driphp\library\client\mongo\WriteException
     */
    public function testInsert(Mongo $mongo)
    {
        define('TEST_NAME', Chars::random(32));

        # 测试插入返回oid
        $res = $mongo->insert([
            'name' => TEST_NAME,
            'value' => 1,
        ]);
        $this->assertTrue(is_string($res) and strlen($res) === 24);

        return $mongo;
    }

    /**
     * @depends testInsert
     * @return Mongo
     * @param Mongo $mongo
     * @throws NotFoundException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function testSelectAndFind(Mongo $mongo)
    {
        # 测试查找,查询刚插入的数据
        $find = $mongo->find([
            'name' => TEST_NAME
        ]);
        $this->assertTrue(TEST_NAME === $find['name']);
        $this->assertTrue(1 === $find['value']);
        # 测试查询2
        $list = $mongo->select([
            'name' => TEST_NAME
        ]);
        $this->assertTrue(TEST_NAME === $list[0]['name']);
        $this->assertTrue(1 === $list[0]['value']);

        # 查询不存在的数据
        $notFound = false;
        try {
            $mongo->find([
                'name' => TEST_NAME . 'XXX'
            ]);
        } catch (NotFoundException $exception) {
            $notFound = true;
        }
        $this->assertTrue($notFound);
        return $mongo;
    }

    /**
     * @depends testSelectAndFind
     * @return Mongo
     * @param Mongo $mongo
     * @throws \driphp\library\client\mongo\WriteException
     */
    public function testUpdate(Mongo $mongo)
    {
        $this->assertTrue($mongo->updateOne([
                'name' => TEST_NAME,
            ], [
                'value' => 2,
            ]) === 1);

        # 再插入2条,更新两条看但返回值是否等于3
        $res = $mongo->insert([
            'name' => TEST_NAME,
            'value' => 1,
        ]);
        $this->assertTrue(is_string($res) and strlen($res) === 24);
        $res = $mongo->insert([
            'name' => TEST_NAME,
            'value' => 1,
        ]);
        $this->assertTrue(is_string($res) and strlen($res) === 24);

        $this->assertTrue(3 === $mongo->updateMany([
                'name' => TEST_NAME,
            ], [
                'value' => 3,
            ]));
        return $mongo;
    }

    /**
     * 测试软删除
     * @depends testUpdate
     * @return Mongo
     * @param Mongo $mongo
     * @throws \MongoDB\Driver\Exception\Exception
     * @throws \driphp\library\client\mongo\WriteException
     */
    public function testRemove(Mongo $mongo)
    {
        $where = [
            'name' => TEST_NAME,
        ];
        $GLOBALS['counter'] = 0;
        $mongo->iterate($where, new class implements IterateHandlerInterface
        {
            public function handle(array $data): bool
            {
                $GLOBALS['counter']++;
                dumpon($data);
                return true;
            }
        });
        $this->assertTrue(3 === $GLOBALS['counter']);
        $this->assertTrue(3 === count($mongo->select($where)));

        $this->assertTrue(1 === $mongo->removeOne($where));
        $this->assertTrue(2 === count($mongo->select($where)));
        $this->assertTrue(2 === $mongo->removeMany($where));
        $this->assertTrue(0 === count($mongo->select($where)));
        return $mongo;
    }

    /**
     * @param Mongo $mongo
     * @depends testRemove
     * @throws \driphp\library\client\mongo\WriteException
     */
    public function testDelete(Mongo $mongo)
    {
        $this->assertTrue(1 === $mongo->deleteOne([
                'name' => TEST_NAME,
            ]));
        $this->assertTrue(2 === $mongo->deleteMany([
                'name' => TEST_NAME,
            ]));
    }


}