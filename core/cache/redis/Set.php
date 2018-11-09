<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 15:49
 */
declare(strict_types=1);

namespace driphp\core\cache\redis;

use driphp\throws\core\RedisException;

/**
 * Class Set 集合
 * @package driphp\core\cache\redis
 */
class Set extends Structure
{

    /**
     * 添加元素到数组中
     * @param string ...$elements
     * @return int 返回实际添加到集合中的数量
     */
    public function add(string ...$elements): int
    {
        $params = array_merge([$this->name], $elements);
        return (int)call_user_func_array([$this->adapter, 'sAdd'], $params);
    }

    /**
     *
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3'); // 'key1' => {'set1', 'set2', 'set3'}
     * $redis->sIsMember('key1', 'set1'); // TRUE
     * $redis->sIsMember('key1', 'setX'); // FALSE
     *
     * @param string $element
     * @return bool
     */
    public function has(string $element): bool
    {
        return (bool)$this->adapter->sIsMember($this->name, $element);
    }

    /**
     *
     * $redis->delete('s');
     * $redis->sAdd('s', 'a');
     * $redis->sAdd('s', 'b');
     * $redis->sAdd('s', 'a');
     * $redis->sAdd('s', 'c');
     * $redis->sMembers('s'); // [ "c","a", "b"]
     *
     * @return array
     */
    public function members()
    {
        return $this->adapter->sMembers($this->name);
    }

    /**
     *
     * $redis->sAdd('key1' , 'set11');
     * $redis->sAdd('key1' , 'set12');
     * $redis->sAdd('key1' , 'set13');          // 'key1' => {'set11', 'set12', 'set13'}
     * $redis->sAdd('key2' , 'set21');
     * $redis->sAdd('key2' , 'set22');          // 'key2' => {'set21', 'set22'}
     * $redis->sMove('key1', 'key2', 'set13');  // 'key1' =>  {'set11', 'set12'}
     *                                          // 'key2' =>  {'set21', 'set22', 'set13'}
     *
     * @param string $element
     * @param string $newSet
     * @return bool If the operation is successful, return TRUE.
     *              If the srcKey and/or dstKey didn't exist, and/or the member didn't exist in srcKey, FALSE is returned.
     *              源集合和目的集合不存在，或者源集合中成员不存在(实际上，目的集不存在时就会创建，返回true)
     */
    public function move(string $element, string $newSet): bool
    {
        return (bool)$this->adapter->sMove($this->name, $newSet, $element);
    }

    /**
     *
     * pop
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3');   // 'key1' => {'set3', 'set1', 'set2'}
     * $redis->sPop('key1');            // 'set1', 'key1' => {'set3', 'set2'}
     * $redis->sPop('key1');            // 'set3', 'key1' => {'set2'}
     *
     * random
     * $redis->sAdd('key1' , 'one');
     * $redis->sAdd('key1' , 'two');
     * $redis->sAdd('key1' , 'three');  // 'key1' => {'one', 'two', 'three'}
     * $redis->sRandMember('key1');     //  "three"
     * $redis->sRandMember('key1', 2);  // [ "one","three" ]
     *
     *
     * @return string
     * @throws RedisException 如果集合为空，则抛出
     */
    public function pop(): string
    {
        $value = $this->adapter->sPop($this->name) ?: '';
        if (false === $value) {
            throw new RedisException("set {$this->name} is empty");
        }
        return $value;
    }

    /**
     * @return string
     * @throws RedisException 如果集合为空，则抛出
     */
    public function random(): string
    {
        $value = $this->adapter->sRandMember($this->name, 1);
        if (false === $value) {
            throw new RedisException("set {$this->name} is empty");
        }
        return reset($value);
    }

    /**
     * @param int $count
     * @return array
     */
    public function randomX(int $count): array
    {
        $value = $this->adapter->sRandMember($this->name, $count);
        if (is_string($value)) {
            $value = [$value];
        }
        return $value;
    }

    /**
     *
     * $redis->sAdd('k', 'v1', 'v2', 'v3');    // int(3)
     * $redis->sRem('k', 'v2', 'v3');          // int(2)   ["v1"]
     *
     * @param string ...$elements
     * @return int The number of elements removed from the set.
     */
    public function remove(string ...$elements): int
    {
        array_unshift($elements, $this->name);
        return (int)call_user_func_array([$this->adapter, 'sRem'], $elements);
    }

    /**
     * 返回集合中元素的数量
     * @return int 如果集合不存在，返回0
     */
    public function count(): int
    {
        return $this->adapter->sCard($this->name);
    }

    /**
     * 计算差集
     * $redis->delete('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s0', '3');
     * $redis->sAdd('s0', '4');
     *
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     *
     * $redis->sDiff('s0', 's1', 's2'); // [ "4" , "2" ]
     *
     * @param string ...$sets
     * @return string[]
     */
    public function diff(string ...$sets): array
    {
        array_unshift($sets, $this->name);
        return (array)call_user_func_array([$this->adapter, 'sDiff'], $sets);
    }

    /**
     *
     * $redis->delete('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     *
     * $redis->sAdd('s1', '3');
     * $redis->sAdd('s1', '1');
     *
     * $redis->sAdd('s2', '3');
     * $redis->sAdd('s2', '4');
     *
     * $redis->sUnion('s0', 's1', 's2'); // [ "3","4","1","2" ]
     *
     * @param string ...$sets
     * @return mixed
     */
    public function union(string ...$sets)
    {
        $sets[] = $this->name;
        return call_user_func_array([$this->adapter, 'sUnion'], $sets);
    }

    /**
     * $redis->delete('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s1', '3');
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     * $redis->sAdd('s2', '4');
     *
     * $redis->sUnionStore('dst', 's0', 's1', 's2'); // 4
     * $redis->sMembers('dst'); // [ "3","4","1","2" ]
     *
     * @param string ...$sets
     * @return int 返回新的集合的数量
     */
    public function unionStore(string ...$sets): int
    {
        $output = array_shift($sets);
        array_unshift($sets, $this->name);
        array_unshift($sets, $output);
        return (int)call_user_func_array([$this->adapter, 'sUnionStore'], $sets);
    }

    /**
     *
     * 交集
     * 多个集合的话返回多个集合同时拥有的元列表
     * $redis->sAdd('key1', 'val1');
     * $redis->sAdd('key1', 'val2');
     * $redis->sAdd('key1', 'val3');
     * $redis->sAdd('key1', 'val4');
     *
     * $redis->sAdd('key2', 'val3');
     * $redis->sAdd('key2', 'val4');
     *
     * $redis->sAdd('key3', 'val3');
     * $redis->sAdd('key3', 'val4');
     *
     * $redis->sInter('key1', 'key2', 'key3'); // [ "val4","val3" ]
     *
     * @param string ...$sets
     * @return string[]
     */
    public function inter(string ...$sets): array
    {
        array_unshift($sets, $this->name);
        return (array)call_user_func_array([$this->adapter, 'sInter'], $sets);
    }

    /**
     *
     * $redis->delete('s0', 's1', 's2');
     *
     * $redis->sAdd('s0', '1');
     * $redis->sAdd('s0', '2');
     * $redis->sAdd('s0', '3');
     * $redis->sAdd('s0', '4');
     *
     * $redis->sAdd('s1', '1');
     * $redis->sAdd('s2', '3');
     *
     * $redis->sDiffStore('dst', 's0', 's1', 's2'); // 2
     * $redis->sMembers('dst');   // [ '4','2']
     *
     * @param string ...$sets
     * @return int 返回新的集合的数量
     */
    public function diffStore(string ...$sets): int
    {
        $output = array_shift($sets);
        array_unshift($sets, $this->name);
        array_unshift($sets, $output);
        return (int)call_user_func_array([$this->adapter, 'sDiffStore'], $sets);
    }

    /**
     *
     * $redis->sAdd('key1', 'val1');
     * $redis->sAdd('key1', 'val2');
     * $redis->sAdd('key1', 'val3');
     * $redis->sAdd('key1', 'val4');
     *
     * $redis->sAdd('key2', 'val3');
     * $redis->sAdd('key2', 'val4');
     *
     * $redis->sAdd('key3', 'val3');
     * $redis->sAdd('key3', 'val4');
     *
     * $redis->sInterStore('output', 'key1', 'key2', 'key3'); // 2
     * $redis->sMembers('output'); // [ "val4", "val3" ]
     *
     * @param string ...$sets
     * @return int 返回新的集合的数量
     */
    public function interStore(string ...$sets): int
    {
        $output = array_shift($sets);
        array_unshift($sets, $this->name);
        array_unshift($sets, $output);
        return (int)call_user_func_array([$this->adapter, 'sInterStore'], $sets);
    }

}