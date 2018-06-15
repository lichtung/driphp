<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 15:51
 */
declare(strict_types=1);


namespace driphp\core\cache;


use driphp\DriException;
use driphp\throws\core\cache\CacheException;

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
     * @throws DriException
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->handler = new \Memcached();
        if (empty($servers = $this->_config['servers'])) {
            throw new DriException('require servers at least one');
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
     * @throws CacheException
     */
    public function set(string $key, $value, int $ttl = 3600): void
    {
        $res = $this->handler->set($key, serialize($value), $ttl);
        if (false === $res) {
            throw new CacheException($this->handler->getResultMessage(), $this->handler->getResultCode());
        }
    }

    /**
     * @param string $key
     * @param null $replace
     * @return mixed
     * @throws CacheException
     */
    public function get(string $key, $replace = null)
    {
        $data = $this->handler->get($key);
        if (false === $data) {
            if ($this->handler->getResultCode() === \Memcached::RES_NOTFOUND) {
                return $replace;
            } else {
                throw new CacheException($this->handler->getResultMessage(), $this->handler->getResultCode());
            }
        }
        return unserialize($data);
    }

    /**
     * @param string $key
     * @return void
     * @throws CacheException
     */
    public function delete(string $key): void
    {
        $res = $this->handler->delete($key);
        if (false === $res) {
            if ($this->handler->getResultCode() !== \Memcached::RES_NOTFOUND) {
                throw new CacheException($this->handler->getResultMessage(), $this->handler->getResultCode());
            }
        }
    }

    /**
     * @return void
     * @throws CacheException
     */
    public function clean(): void
    {
        $res = $this->handler->flush();
        if (false === $res) {
            throw new CacheException($this->handler->getResultMessage(), $this->handler->getResultCode());
        }
    }


}