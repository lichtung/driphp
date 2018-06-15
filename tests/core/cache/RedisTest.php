<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/22 0022
 * Time: 20:54
 */
declare(strict_types=1);


namespace dripex\test\core\cache;


use driphp\core\cache\Redis;
use driphp\tests\UniTest;

class RedisTest extends UniTest
{
    /**
     * @return Redis
     * @throws \driphp\throws\core\cache\RedisException
     */
    public function testRedis()
    {
        $redis = Redis::getInstance();
        # get a item which not exist in redis
        $this->assertTrue('__not_exist__' === $redis->get('not_exist', '__not_exist__'));
        $redis->set('key_exist', ['__key_exist__']);
        $this->assertTrue(['__key_exist__'] === $redis->get('key_exist', null));
        $redis->delete('key_exist');
        $this->assertTrue('__not_exist__' === $redis->get('key_exist', '__not_exist__'));

        $key = 'expire_after_two_seconds';
        $value = md5($key);
        $redis->set($key, $value, 2);
        $this->assertTrue($value === $redis->get($key));
        $this->assertTrue(true === $redis->has($key));
        $this->assertTrue(true === $redis->has($key));
//        sleep(1);
//        $this->assertTrue($value === $redis->get($key));
//        $this->assertTrue(true === $redis->has($key));
//        usleep(1000001);
//        $this->assertTrue(null === $redis->get($key));
//        $this->assertTrue(false === $redis->has($key));


        $key = 'testSetTTLAndHasDeleteClean';
        $value = md5($key);

        $redis->set($key, $value);
        $redis->delete($key);
        $this->assertTrue(false === $redis->has($key));
        $redis->set($key . '-1', $value);
        $redis->set($key . '-2', $value);
        $redis->clean();
        $this->assertTrue(false === $redis->has($key . '-1'));
        $this->assertTrue(false === $redis->has($key . '-2'));


        return $redis;
    }

    /**
     * @depends testRedis
     * @param Redis $redis
     * @return Redis
     * @throws
     */
    public function testHash(Redis $redis)
    {
        $redis->delete('hash_demo');
        $hash = $redis->getHash('hash_demo');
        # set
        $hash->set('hash_key1', 'hash_val1');
        $this->assertTrue(1 === $hash->length());
        $hash->setInBatch([
            'hash_key2' => 'hash_val2',
            'hash_key3' => 'hash_val3',
        ], false);
        $this->assertTrue(3 === $hash->length());
        $hash->setInBatch([
            'hash_key5' => 'hash_val5',
            'hash_key6' => 'hash_val6',
            'hash_key7' => 'hash_val7',
        ], true);
        $this->assertTrue(3 === $hash->length());


        # getAll
        $this->assertTrue([
                'hash_key5' => 'hash_val5',
                'hash_key6' => 'hash_val6',
                'hash_key7' => 'hash_val7',
            ] === $hash->getAll());
        $this->assertTrue([
                'hash_key6' => 'hash_val6',
                'hash_key7' => 'hash_val7',
            ] === $hash->getAll('hash_key6', 'hash_key7'));

        # values
        $this->assertTrue([
                'hash_val5',
                'hash_val6',
                'hash_val7',
            ] === $hash->values());
        # keys
        $this->assertTrue([
                'hash_key5',
                'hash_key6',
                'hash_key7',
            ] === $hash->keys());

        # get
        unset($hash);
        $hash = (new Redis())->getHash('hash_demo');
        $this->assertTrue('hash_val5' === $hash->get('hash_key5', 'hash_key5_not_exist'));
        $this->assertTrue('hash_key52_not_exist' === $hash->get('hash_key52', 'hash_key52_not_exist'));
        #has
        $this->assertFalse($hash->has('hash_key55'));

        $this->assertTrue($hash->has('hash_key5'));
        $this->assertTrue($hash->has('hash_key6'));
        $this->assertTrue($hash->has('hash_key7'));
        # delete
        $hash->delete('hash_key7');
        $this->assertFalse($hash->has('hash_key7'));

        $hash->delete('hash_key5', 'hash_key6');
        $this->assertFalse($hash->has('hash_key5'));
        $this->assertFalse($hash->has('hash_key6'));
        return $redis;
    }

    /**
     * @depends testHash
     * @param Redis $redis
     * @return Redis
     * @throws
     */
    public function testTransaction(Redis $redis)
    {
        # rollback
        $redis->beginTransaction();
        $redis->set('hello_transaction1', '1');
        $this->assertTrue($redis->has('hello_transaction1'));
        $redis->rollback();
        $this->assertFalse($redis->has('hello_transaction1'));

        # commit
        $redis->beginTransaction();
        $redis->set('hello_transaction2', '2');
        $this->assertTrue($redis->has('hello_transaction2'));
        $redis->commit();
        $this->assertTrue($redis->has('hello_transaction2'));

        $redis->delete('hello_transaction1');
        $redis->delete('hello_transaction2');
        return $redis;
    }

    /**
     * @depends testTransaction
     * @param Redis $redis
     * @return Redis
     * @throws
     */
    public function testList(Redis $redis)
    {
        $list = $redis->getList('demo_list');
        # basic : get set push length range
        $this->assertTrue(null === $list->get(0));
        $this->assertTrue('not_exist' === $list->get(1, 'not_exist'));
        $this->assertTrue(false === $list->set(0, 'first_element')); # 越界

        $list->push('1th_element');
        $list->push('2th_element');
        $this->assertTrue('2th_element' === $list->get(0));
        $this->assertTrue('1th_element' === $list->get(1));
        $this->assertTrue(2 === $list->length());

        $list->set(0, 'second_element');
        $this->assertTrue($list->get(0) === 'second_element');
        $this->assertTrue([
                'second_element', '1th_element',
            ] === $list->range(0));
        $this->assertTrue(['1th_element',] === $list->range(-1, -1));
        $this->assertTrue(['second_element', '1th_element',] === $list->range(-2, -1));
        $this->assertTrue(['second_element', '1th_element',] === $list->range(0, 1));

        # push right
        $list->push('0th_element', false);
        $this->assertTrue('second_element' === $list->get(0));
        $this->assertTrue('1th_element' === $list->get(1));
        $this->assertTrue('0th_element' === $list->get(2));
        $this->assertTrue(3 === $list->length());
        $this->assertTrue([
                'second_element', '1th_element', '0th_element',
            ] === $list->range(0));

        $this->assertTrue($list->trim(1, -1) === true);
        $this->assertTrue([
                '1th_element', '0th_element',
            ] === $list->range(0));
        # remove
        $list->push('0th_element');
        $list->push('0th_element');
        $list->remove('0th_element', 2);
        $this->assertTrue([
                '1th_element', '0th_element',
            ] === $list->range(0));

        $list->push('0th_element');
        $this->assertTrue(true === $list->trim(1, 1));
        $this->assertTrue([
                '1th_element',
            ] === $list->range(0));
        $list->push('2th_element');
        $list->push('0th_element', false);
        $this->assertTrue([
                '2th_element', '1th_element', '0th_element',
            ] === $list->range(0));

        # insert
        $list->insert('0th_element', '0.5th_element', false);
        $this->assertTrue([
                '2th_element', '1th_element', '0.5th_element', '0th_element',
            ] === $list->range(0));
        $list->insert('2th_element', '1.5th_element', true);
        $this->assertTrue([
                '2th_element', '1.5th_element', '1th_element', '0.5th_element', '0th_element',
            ] === $list->range(0));
        # double insert
        $list->insert('1th_element', '1th_element', true);
        $this->assertTrue([
                '2th_element', '1.5th_element', '1th_element', '1th_element', '0.5th_element', '0th_element',
            ] === $list->range(0));

        $list->insert('1th_element', '~th_element', true);
        $this->assertTrue([
                '2th_element', '1.5th_element', '1th_element', '~th_element', '1th_element', '0.5th_element', '0th_element',
            ] === $list->range(0));

        # pop
        $this->assertTrue('2th_element' === $list->pop(null, true));
        $this->assertTrue([
                '1.5th_element', '1th_element', '~th_element', '1th_element', '0.5th_element', '0th_element',
            ] === $list->range(0));
        $this->assertTrue('0th_element' === $list->pop(null, false));
        $this->assertTrue([
                '1.5th_element', '1th_element', '~th_element', '1th_element', '0.5th_element',
            ] === $list->range(0));

        $this->assertTrue(true === $list->clean());
        $this->assertTrue(0 === $list->length());

        return $redis;
    }

    /**
     * @depends testList
     * @param Redis $redis
     * @return void
     * @throws
     */
    public function testSet(Redis $redis)
    {
        $set = $redis->getSet('demo_set');

        # add
        $this->assertTrue(1 === $set->add('set1'));
        $this->assertTrue(1 === $set->add('set2', 'set1'));
        $this->assertTrue($set->count() === 2);

        # has
        $this->assertTrue($set->has('set1'));
        $this->assertFalse($set->has('set11'));

        # members
        $this->assertTrue(['set1', 'set2'] === $set->members() or ['set2', 'set1'] === $set->members());

        # random,randomX
        $count = 10;
        while ($count--) {
            $val = $set->random();
            echo 'Random:' . $val . PHP_EOL;
            echo 'RandomX:' . var_export($set->randomX((int)substr($val, 3)), true) . PHP_EOL;
        }

        # pop length
        $values = [];
        $values[] = $set->pop();
        $this->assertTrue($set->count() === 1);
        $values[] = $set->pop();
        $this->assertTrue($set->count() === 0);
        $this->assertTrue(['set1', 'set2'] === $values or ['set2', 'set1'] === $values);


        # remove
        $set->add('set1');
        $set->add('set2', 'set3', 'set4', 'set5');
        $set->remove('set3');
        $this->assertTrue(4 === $set->count());
        $set->remove('set4', 'set5');
        $this->assertTrue(2 === $set->count());

        # move
        $this->assertTrue(3 === $set->add('set2', 'set3', 'set4', 'set5'));
        $this->assertTrue(true === $set->move('set5', 'demo_set2'));
        $set2 = $redis->getSet('demo_set2');
        $this->assertTrue('set5' === $set2->random());
        $this->assertTrue(4 === $set->count());
        $this->assertArrayEqual(['set1', 'set2', 'set3', 'set4'], $set->members());

        # $set ['set1', 'set2', 'set3', 'set4']
        # $set2 ['set5']
        $set3 = $redis->getSet('demo_set3');
        $set3->add('set6', 'set4');

        # diff 差集，相当于减法
        $this->assertArrayEqual($set->diff('demo_set3'), ['set1', 'set2', 'set3']);
        $this->assertArrayEqual($set->diff('demo_set2'), ['set1', 'set2', 'set3', 'set4']);
        $this->assertArrayEqual($set->diff('demo_set2', 'demo_set3'), ['set1', 'set2', 'set3',]);
        # diffStore
        $this->assertTrue($set->diffStore('demo_diff_store', 'demo_set2', 'demo_set3') === 3); # ['set1', 'set2', 'set3',]
        $this->assertArrayEqual($redis->getSet('demo_diff_store')->members(), ['set1', 'set2', 'set3',]);

        # inter交集
        $set4 = $redis->getSet('demo_set4');
        $set4->add('ele1', 'ele2', 'ele3', 'ele4');
        $set5 = $redis->getSet('demo_set5');
        $set5->add('ele2', 'ele3');
        $set6 = $redis->getSet('demo_set6');
        $set6->add('ele3', 'ele4');
        $this->assertArrayEqual($set4->inter('demo_set5'), ['ele2', 'ele3',]);
        $this->assertArrayEqual($set4->inter('demo_set6'), ['ele3', 'ele4']);
        $this->assertArrayEqual($set4->inter('demo_set5', 'demo_set6'), ['ele3']);
        # interStore
        $this->assertTrue($set4->interStore('demo_inter_store', 'demo_set5', 'demo_set6') === 1);# ['ele3']
        $this->assertArrayEqual($redis->getSet('demo_inter_store')->members(), ['ele3']);

        # union 并集
        $set7 = $redis->getSet('demo_set7');
        $set7->add('ele1', 'ele2', 'ele4');
        $redis->getSet('demo_set8')->add('ele8');
        $this->assertArrayEqual($set7->union('demo_set5'), ['ele1', 'ele2', 'ele3', 'ele4']);
        $this->assertArrayEqual($set7->union('demo_set8'), ['ele1', 'ele2', 'ele4', 'ele8']);
        $this->assertArrayEqual($set7->union('demo_set5', 'demo_set8'),
            ['ele1', 'ele2', 'ele3', 'ele4', 'ele8']);
        # unionStore
        $this->assertTrue($set7->unionStore('demo_union_store', 'demo_set5', 'demo_set8') === 5);# ['ele1', 'ele2', 'ele3', 'ele4', 'ele8']
        $this->assertArrayEqual($redis->getSet('demo_union_store')->members(),
            ['ele1', 'ele2', 'ele3', 'ele4', 'ele8']);


    }


}