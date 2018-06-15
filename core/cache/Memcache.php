<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 15:53
 */
declare(strict_types=1);


namespace driphp\core\cache;

use Memcache as M;
use driphp\throws\core\cache\CacheException;

class Memcache extends Driver
{
    /**
     * @inheritdoc
     */
    public function clean()
    {
        return $this->handler->flush();
    }

    public function delete(string $key)
    {
        return $this->handler->delete($this->key . $key);
    }

    /**
     * @var M
     */
    private $handler = null;

    protected $config = [
        'ext' => 0, # 选取扩展
        'key' => '',
        'servers' => [
            [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 1,
            ]
        ],
        # 超时设置
        'timeout' => 1,
    ];
    protected $key = '';

    protected $isMemcached = true;

    /**
     * Memcache constructor.
     * @param array $config
     * @throws CacheException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->key = SR_DEBUG_ON ? '' : sha1($this->config['key']);
        $this->handler = new M();
        if (empty($servers = $this->config['servers'])) {
            throw new CacheException('require servers at least one');
        }

        foreach ($servers as $server) {
            $host = isset($server['host']) ? $server['host'] : '127.0.0.1';
            $port = isset($server['port']) ? $server['port'] : 11211;
            $weight = isset($server['weight']) ? $server['weight'] : 1;
            // Third parameter is persistence and defaults to TRUE.
            $this->handler->addServer($host, $port, true, $weight, $this->config['timeout']);
        }
    }

    /**
     * 设置缓存
     * @param string $id 缓存ID
     * @param mixed $data 缓存数据
     * @param int $ttl 缓存期,默认1小时
     * @return void
     */
    public function set(string $id, $data, int $ttl = 3600): void
    {
        $id = $this->key . $id;
        $data = serialize($data);
        $this->handler->set($id, $data, 0, $ttl);
    }

    /**
     * Set a new expiration on an item
     * @param string|int $id
     * @param int $ttl
     * @return bool
     */
    public function touch($id, $ttl = 3600)
    {
        $id = $this->key . $id;
        # memcache没有touch功能
        $data = $this->handler->get($id);
        return $this->handler->set($id, $data, 0, $ttl);
    }

    /**
     * 判断文件是否存在
     * @param string $id 缓存ID
     * @return bool
     */
    public function has($id)
    {
        return false !== $this->handler->get($this->key . $id);
    }

    /**
     * 获取缓存
     * @param string $id 缓存ID
     * @param mixed $replace 缓存不存在时默认返回的值,默认为null
     * @return mixed
     */
    public function get(string $id, $replace = null)
    {
        $data = $this->handler->get($this->key . $id);
        return false === $data ? $replace : unserialize($data);
    }


    /**
     * Class destructor
     * Closes the connection to Memcache(d) if present.
     * @return    void
     */
    public function __destruct()
    {
        $this->handler->close();
    }

    /**
     * 魔术转移到handler上
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->handler, $name], $arguments);
    }

}