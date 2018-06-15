<?php
/**
 * User: linzhv@qq.com
 * Date: 24/04/2018
 * Time: 14:53
 */
declare(strict_types=1);


namespace driphp\core\cache\redis;

use Redis;
use driphp\throws\core\cache\RedisException;

/**
 * Class Lists
 * @package driphp\core\cache\redis
 */
class Lists
{

    /**
     * @var Redis
     */
    private $redis;
    private $listName = '';

    /**
     * Lists constructor.
     * @param string $listName
     * @param Redis $redis
     */
    public function __construct(string $listName, Redis $redis)
    {
        $this->listName = $listName;
        $this->redis = $redis;
    }

    /**
     * 通过索引获取列表中的元素
     * @param int $index
     * @param mixed $replace
     * @return mixed
     */
    public function get(int $index, $replace = null)
    {
        $val = $this->redis->lIndex($this->listName, $index);
        return $val === false ? $replace : $val;
    }

    /**
     * Set the list at index with the new value.
     * TRUE if the new value is set. FALSE if the index is out of range, or data type identified by key
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lGet('key1', 0);     // 'A'
     * $redis->lSet('key1', 0, 'X');
     * $redis->lGet('key1', 0);     // 'X'
     * @param int $index
     * @param $value
     * @return bool 返回设置是否成功
     */
    public function set(int $index, $value): bool
    {
        return (bool)$this->redis->lSet($this->listName, $index, $value);
    }

    /**
     *
     * <pre>
     *
     *  $redis->lPush('l', 'v1', 'v2', 'v3', 'v4')   // int(4)
     *  $redis->lRange('l', 0, -1) ; // [ "v4","v3","v2","v1",  ]
     *
     *  $redis->rPush('l', 'v1', 'v2', 'v3', 'v4');    // int(4)
     *  $redis->lRange('l', 0, -1) ; // [ "v1","v2","v3","v4",  ]
     *
     * </pre>
     * @param string $element
     * @param bool $left
     * @param bool $createIfNotExist 默认为true创建列表如果列表不存在；false时列表不存在返回0
     * @return int 返回新的长度
     * @throws RedisException
     */
    public function push(string $element, bool $left = true, bool $createIfNotExist = true): int
    {
        if ($createIfNotExist) {
            $value = $left ? $this->redis->lPush($this->listName, $element) : $this->redis->rPush($this->listName, $element);
        } else {
            $value = $left ? $this->redis->lPushx($this->listName, $element) : $this->redis->rPushx($this->listName, $element);
        }
        if (false === $value) {
            throw new RedisException("Key {$this->listName} is not a list!");
        }
        return $value;
    }

    /**
     * 获取列表长度
     * @return int
     */
    public function length()
    {
        return $this->redis->lLen($this->listName);
    }

    /**
     * 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');
     * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
     * @param int $start
     * @param int $stop 注意：stop所在的位置也会被包含在内
     * @return array
     */
    public function range(int $start, int $stop = -1): array
    {
        return (array)$this->redis->lRange($this->listName, $start, $stop);
    }

    /**
     * clean the list
     * @return bool
     */
    public function clean(): bool
    {
        return (bool)$this->redis->lTrim($this->listName, 1, 0); # it will remove all elements if start is greater than stop
    }

    /**
     * Trims an existing list so that it will contain only a specified range of elements.
     *
     * @param int $start
     * @param int $stop
     * @return bool
     */
    public function trim(int $start, int $stop = -1): bool
    {
        return (bool)$this->redis->lTrim($this->listName, $start, $stop);
    }

    /**
     * $redis->lRange('key1', 0, -1);   // array('A', 'A', 'C', 'B', 'A')
     * $redis->lRem('key1', 'A', 2);    // 2
     * $redis->lRange('key1', 0, -1);   // array('C', 'B', 'A')
     *
     * @param   string $value
     * @param   int $count
     * @return int 返回删除元素的数量
     */
    public function remove(string $value, int $count): int
    {
        return (int)$this->redis->lRem($this->listName, $value, $count);
    }

    /**
     * If the list didn't exists, or the pivot didn't exists, the value is not inserted.
     * 如果列表不存在返回0，参照物不存在时返回-1， 两种情况下值不会被插入
     * @param string $pivot 参照物
     * @param string $value 插入值
     * @param bool $after true时插入到后部，false时插入到前面
     * @return int
     */
    public function insert(string $pivot, string $value, bool $after = true): int
    {
        return (int)$this->redis->lInsert($this->listName, $after ? Redis::AFTER : Redis::BEFORE, $pivot, $value);
    }


    /**
     * 移除并返回列表的元素
     *
     *
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lPop('key1');        // key1 => [ 'B', 'C' ]
     *
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->rPop('key1');        // key1 => [ 'A', 'B' ]
     *
     * @param mixed $replace
     * @param bool $left 默认为true时返回
     * @return mixed|string
     */
    public function pop($replace = null, bool $left = true)
    {
        if ($left) {
            $value = $this->redis->lPop($this->listName);
        } else {
            $value = $this->redis->rPop($this->listName);
        }
        return false === $value ? $replace : $value;
    }

}