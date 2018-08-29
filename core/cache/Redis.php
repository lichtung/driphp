<?php
/**
 * User: linzhv@qq.com
 * Date: 24/04/2018
 * Time: 14:52
 */
declare(strict_types=1);


namespace driphp\core\cache;


use driphp\Component;
use driphp\core\cache\redis\Hash;
use driphp\core\cache\redis\Lists;
use driphp\core\cache\redis\Set;
use driphp\DriverInterface;
use driphp\throws\cache\RedisException;
use driphp\throws\cache\redis\ConnectionException;

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

    protected $secret = '';
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
     * @throws RedisException
     */
    public function __construct(array $config = [], Component $context = null)
    {
        parent::__construct($config);
        $this->secret = sha1($this->config['secret']);
        $this->handler();
    }

    /**
     * @return Redis
     * @throws RedisException
     */
    public static function getInstance(): Redis
    {
        static $instance = null;
        $instance or $instance = new static();
        return $instance;
    }

    /**
     * @return \Redis
     * @throws RedisException
     */
    public function handler(): \Redis
    {
        if (!$this->handler) {
            $this->handler = new \Redis();
            $host = $this->config['host'];
            $port = $this->config['port'];
            $timeout = $this->config['timeout'];
            $password = $this->config['password'];
            $database = $this->config['database'];

            $error = '';
            if (!$this->handler->connect($host, ($host[0] === '/' ? 0 : $port), $timeout)) {
                $error = "Host[{$host}:{$port}] connect failed ";
            } elseif ($password and !$this->handler->auth($password)) {
                $error = "Authorize[{$host}:{$port} $password] authentication failed ";
            } elseif ($database and !$this->handler->select($database)) {
                $error = "Database[{$host}:{$port}/$database] connect failed ";
            }

            if ($error) throw new ConnectionException($error);
        }
        return $this->handler;
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


    ################### transaction ####################

    /**
     * @return void
     */
    public function beginTransaction()
    {
        $this->handler->multi();
    }

    /**
     * @return void
     */
    public function rollback()
    {
        $this->handler->discard();
    }

    /**
     * @return void
     */
    public function commit()
    {
        $this->handler->exec();
    }

    ################### complex structure ####################

    static $_instances = [];

    public function getHash(string $name): Hash
    {
        if (!isset(self::$_instances[$name])) {
            self::$_instances[$name] = new Hash($name, $this->handler);
        }
        return self::$_instances[$name];
    }

    /**
     * Redis List
     * @param string $name
     * @return Lists
     */
    public function getList(string $name): Lists
    {
        if (!isset(self::$_instances[$name])) {
            self::$_instances[$name] = new Lists($name, $this->handler);
        }
        return self::$_instances[$name];
    }

    public function getSet(string $name): Set
    {
        if (!isset(self::$_instances[$name])) {
            self::$_instances[$name] = new Set($name, $this->handler);
        }
        return self::$_instances[$name];
    }
}