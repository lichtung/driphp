<?php
/**
 * User: linzhv@qq.com
 * Date: 24/04/2018
 * Time: 14:51
 */
declare(strict_types=1);


namespace driphp\core\cache;


use driphp\throws\core\cache\RedisException;

/**
 * 缓存驱动
 * @author zhonghuanglin
 */
abstract class Driver
{
    protected $config = [];

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config and $this->config = array_merge($this->config, $config);
    }

    /**
     * set key-value pair to cache
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     * @throws RedisException This exception will be thrown if set item failed
     */
    abstract public function set(string $key, $value, int $ttl = 3600);

    /**
     * get value from cache by key
     * @param string $key
     * @param mixed $replace This value will return if target not exist
     * @return mixed
     * @throws RedisException This exception will be thrown if something goes wrong
     */
    abstract public function get(string $key, $replace = null);

    /**
     * delete key-value pair
     * @param string $key
     * @return void
     * @throws RedisException
     */
    abstract public function delete(string $key);

    /**
     * delete all key-value pair (dangerous action)
     * @return void
     * @throws RedisException
     */
    abstract public function clean();

}