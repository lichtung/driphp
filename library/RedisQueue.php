<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:10
 */

namespace driphp\library;


use driphp\Component;
use driphp\core\cache\Redis;

/**
 * Class RedisQueue
 * @method RedisQueue factory(array $config = []) static
 * @package driphp\library
 */
class RedisQueue extends Component
{
    protected $config = [
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => 6379,
        'timeout' => 7.0,
        'database' => 0,
        'key' => 'default',
    ];

    /** @var \Redis */
    private $redis;
    /** @var string */
    private $key;

    protected function initialize()
    {
        $this->key = $this->config['key'];
        $this->redis = Redis::factory($this->config);
    }

    /**
     * 从后添加元素
     * @param array|string $element
     * @return bool
     */
    public function push($element): bool
    {
        return false !== $this->redis->rPush($this->key, $element);
    }

    /**
     * 从前添加元素
     * @param $element
     * @return bool
     */
    public function unshift($element): bool
    {
        return false !== $this->redis->lPush($this->key, $element);
    }

    /**
     * 删除元素
     * @param $element
     * @param int $limit
     * @return bool
     */
    public function remove($element, int $limit = 0): bool
    {
        return $this->redis->lRem($this->key, $element, $limit) > 0;
    }

    /**
     * 返回并删除首个元素
     * get and removes the first element of the list.
     * @return array|string|null
     */
    public function shift()
    {
        $element = $this->redis->lPop($this->key);
        return false === $element ? null : $element;
    }

    /**
     * 返回并删除最后一个元素
     * Returns and removes the last element of the list.
     * @return array|string|null
     */
    public function pop()
    {
        $element = $this->redis->rPop($this->key);
        return false === $element ? null : $element;
    }

    /**
     * 获取首个元素
     * @return string|array|null 元素不存在时返回null
     */
    public function first()
    {
        $list = $this->redis->lRange($this->key, 0, 1);//第1个
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
        $list = $this->redis->lRange($this->key, -1, -1);//最后一个到最后一个
        if (count($list)) {
            return reset($list);
        } else {
            return null;
        }
    }

    public function all(): array
    {
        $list = $this->redis->lRange($this->key, 0, -1);//第一个到最后一个
        return false === $list ? [] : $list;
    }

    /**
     * @return bool
     */
    public function clean(): bool
    {
        $this->redis->del($this->key);//返回删除的数目
        return true;
    }

    /**
     * 返回队列长度
     * @return int
     */
    public function length(): int
    {
        $len = $this->redis->lLen($this->key);
        return false === $len ? 0 : $len;
    }

}