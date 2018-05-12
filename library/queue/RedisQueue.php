<?php
/**
 * Created by linzh.
 * Email: 784855684@qq.com
 * Date: 5/31/17 5:11 PM
 */

namespace sharin\library\queue;

use sharin\core\cache\Redis;


/**
 * Class RedisQueue Queue的redis实现
 * @package sharin\library\quene
 */
class RedisQueue implements QueueInterface
{

    public $key = 'default';
    /**
     * @var Redis
     */
    private static $redis = null;

    /**
     * @param string $key
     * @return mixed
     */
    public static function getInstance(string $key)
    {
        static $_instances = [];
        if (!isset($_instances[$key])) {
            $_instances[$key] = new RedisQueue($key);
        }
        return $_instances[$key];
    }

    /**
     * RedisQueue constructor.
     * @param string $key
     * @param int $dbIndex
     * @throws \sharin\throws\core\cache\RedisException
     */
    public function __construct(string $key, int $dbIndex = 0531)
    {
        if (!self::$redis) {
            self::$redis = Redis::getInstance()->handler();
        }
        self::$redis->select($dbIndex);
        $this->key = $key;
    }

    /**
     * 从后添加元素
     * @param array|string $element
     * @param bool $append 默认是追加，为false是在前面添加
     * @return bool
     */
    public function push($element, bool $append = true): bool
    {
        return false !== self::$redis->rPush($this->key, $element);
    }

    /**
     * 从前添加元素
     * @param $element
     * @return bool
     */
    public function unshift($element): bool
    {
        return false !== self::$redis->lPush($this->key, $element);
    }

    /**
     * 删除元素
     * @param $element
     * @param int $limit
     * @return bool
     */
    public function remove($element, int $limit = 0): bool
    {
        return self::$redis->lRem($this->key, $element, $limit) > 0;
    }

    /**
     * 返回并删除首个元素
     * get and removes the first element of the list.
     * @return array|string|null
     */
    public function shift()
    {
        $element = self::$redis->lPop($this->key);
        return false === $element ? null : $element;
    }

    /**
     * 返回并删除最后一个元素
     * Returns and removes the last element of the list.
     * @return array|string|null
     */
    public function pop()
    {
        $element = self::$redis->rPop($this->key);
        return false === $element ? null : $element;
    }

    /**
     * 获取首个元素
     * @return string|array|null 元素不存在时返回null
     */
    public function first()
    {
        $list = self::$redis->lRange($this->key, 0, 1);//第1个
        if (count($list)) {
            return reset($list);
        } else {
            return null;
        }
    }

    /**
     * 获取最后一个元素
     * @return string|array|null 元素不存在时返回null
     */
    public function last()
    {
        $list = self::$redis->lRange($this->key, -1, -1);//最后一个到最后一个
        if (count($list)) {
            return reset($list);
        } else {
            return null;
        }
    }

    public function all(): array
    {
        $list = self::$redis->lRange($this->key, 0, -1);//第一个到最后一个
        return false === $list ? [] : $list;
    }

    /**
     * @return bool
     */
    public function clean(): bool
    {
        self::$redis->del($this->key);//返回删除的数目
        return true;
    }

    /**
     * 返回队列长度
     * @return int
     */
    public function length(): int
    {
        $len = self::$redis->lLen($this->key);
        return false === $len ? 0 : $len;
    }

}