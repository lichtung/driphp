<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 10:31
 */

namespace driphp\core\cache;

use driphp\throws\cache\MemcachedException;

class Memcached extends Driver
{
    /**
     * @var \Memcached
     */
    protected $handler = null;
    protected $_config = [
        'secret' => '',
        'servers' => [
            [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 1,
            ]
        ],
        'timeout' => 1,
    ];

    /**
     * Memcached constructor.
     * @param array $config
     * @throws MemcachedException
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->handler = new \Memcached();
        if (empty($servers = $this->_config['servers'])) {
            throw new MemcachedException('require servers at least one');
        }
        $this->handler->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->_config['timeout']);

        foreach ($servers as $server) {
            $this->handler->addServer($server['host'] ?? '127.0.0.1', $server['port'] ?? 11211, $server['weight'] ?? 1);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     * @throws MemcachedException
     */
    public function set(string $key, $value, int $ttl = 3600)
    {
        $res = $this->handler->set($key, serialize($value), $ttl);
        if (false === $res) {
            throw new MemcachedException($this->handler->getResultMessage(), $this->handler->getResultCode());
        }
    }

    /**
     * @param string $key
     * @param null $replace
     * @return mixed
     * @throws MemcachedException
     */
    public function get(string $key, $replace = null)
    {
        $data = $this->handler->get($key);
        if (false === $data) {
            if ($this->handler->getResultCode() === \Memcached::RES_NOTFOUND) {
                return $replace;
            } else {
                throw new MemcachedException($this->handler->getResultMessage(), $this->handler->getResultCode());
            }
        }
        return unserialize($data);
    }

    /**
     * @param string $key
     * @return void
     * @throws MemcachedException
     */
    public function delete(string $key)
    {
        $res = $this->handler->delete($key);
        if (false === $res) {
            if ($this->handler->getResultCode() !== \Memcached::RES_NOTFOUND) {
                throw new MemcachedException($this->handler->getResultMessage(), $this->handler->getResultCode());
            }
        }
    }

    /**
     * @return void
     * @throws MemcachedException
     */
    public function clean()
    {
        $res = $this->handler->flush();
        if (false === $res) {
            throw new MemcachedException($this->handler->getResultMessage(), $this->handler->getResultCode());
        }
    }


}