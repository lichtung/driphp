<?php
/**
 * User: linzhv@qq.com
 * Date: 24/04/2018
 * Time: 14:52
 */
declare(strict_types=1);


namespace driphp\core\cache;

use driphp\Component;
use driphp\core\RedisManager;
use driphp\DriverInterface;
use driphp\throws\cache\RedisException;

/**
 * Class Redis
 * @package driphp\core\cache
 */
class Redis extends Driver implements DriverInterface
{

    /**
     * @var \Redis
     */
    protected $handler = null;

    protected $config = [
        'host' => '127.0.0.1',
        'secret' => '',
        'password' => NULL,
        'port' => 6379,
        'timeout' => 7.0,
        'database' => 0
    ];

    /**
     * Redis constructor.
     * @param array $config
     * @param Component $context
     */
    public function __construct(array $config = [], Component $context = null)
    {
        parent::__construct($config);
        $this->handler = RedisManager::factory()->getAdapter();
    }


    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     * @throws RedisException
     */
    public function set(string $key, $value, int $ttl = 3600)
    {
        $data = serialize($value);
        if ($ttl ? $this->handler->setex($key, $ttl, $data) : $this->handler->set($key, $data)) {
        } else {
            $errMsg = $this->handler->getLastError(); # A string with the last returned script based error message, or NULL if there is no error
            if (isset($errMsg)) {
                $this->handler->clearLastError(); # Clear the last error message
                throw new RedisException($errMsg);
            }
        }
    }

    public function get(string $key, $replace = null)
    {
        $data = $this->handler->get($key); # If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned.
        return (false === $data) ? $replace : unserialize($data);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return false !== $this->handler->get($key);
    }

    public function delete(string $key)
    {
        $this->handler->delete($key); # Number of keys deleted.
    }

    public function clean()
    {
        $this->handler->flushDB(); # Always return TRUE.
    }

    public function __destruct()
    {
        $this->handler and $this->handler->close();
    }
}