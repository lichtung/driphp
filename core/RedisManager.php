<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 17:00
 */

namespace driphp\core;


use driphp\Component;
use driphp\core\redis\Hash;
use driphp\core\redis\Lists;
use driphp\core\redis\Set;
use driphp\throws\core\RedisConnectException;
use driphp\throws\core\RedisException;

/**
 * Class RedisManager Redis操作管理起
 * @method RedisManager factory(array $config = []) static
 * @package driphp\core
 */
class RedisManager extends Component
{

    /** @var \Redis */
    protected $adapter = null;

    protected $config = [
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => 6379,
        'timeout' => 7.0,
        'database' => 0
    ];

    protected function initialize()
    {
    }


    /**
     * @throws RedisException
     */
    public function checkError()
    {
        $errMsg = $this->getAdapter()->getLastError(); # A string with the last returned script based error message, or NULL if there is no error
        if (isset($errMsg)) {
            $this->getAdapter()->clearLastError(); # Clear the last error message
            throw new RedisException($errMsg);
        }
    }

    /**
     * @return $this
     * @throws RedisConnectException
     */
    public function connect()
    {
        $this->getAdapter();
        return $this;
    }

    /**
     * @return \Redis
     * @throws RedisConnectException
     */
    public function getAdapter(): \Redis
    {
        if (!$this->adapter) {
            $this->adapter = new \Redis();
            $host = $this->config['host'];
            $port = $this->config['port'];
            $timeout = $this->config['timeout'];
            $password = $this->config['password'];
            $database = $this->config['database'];
            $error = '';
            if (!$this->adapter->connect($host, ($host[0] === '/' ? 0 : $port), $timeout)) {
                $error = "Host[{$host}:{$port}] connect failed ";
            }
            if ($password and !$this->adapter->auth($password)) {
                $error = "Authorize[{$host}:{$port} $password] authentication failed ";
            }
            if ($database and !$this->adapter->select($database)) {
                $error = "Database[{$host}:{$port}/$database] connect failed ";
            }
            if ($error) {
                throw new RedisConnectException($error);
            }
        }
        return $this->adapter;
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
        if ($ttl ? $this->getAdapter()->setex($key, $ttl, $data) : $this->getAdapter()->set($key, $data)) {
        } else {
            $this->checkError();
        }
    }

    /**
     * @param string $key
     * @param null $replace
     * @return mixed
     * @throws RedisException
     */
    public function get(string $key, $replace = null)
    {
        $data = $this->getAdapter()->get($key); # If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned.
        $this->checkError();
        return (false === $data) ? $replace : unserialize($data);
    }

    /**
     * @param string $key
     * @return bool
     * @throws RedisConnectException
     */
    public function has(string $key): bool
    {
        return (bool)$this->getAdapter()->exists($key);
    }

    /**
     * @param string $key
     * @throws RedisConnectException
     */
    public function delete(string $key)
    {
        $this->getAdapter()->delete($key); # Number of keys deleted.
    }

    /**
     * @deprecated 危险
     * @return void
     * @throws RedisConnectException
     */
    public function clean()
    {
        $this->getAdapter()->flushDB(); # Always return TRUE.
    }

    /**
     * @throws RedisConnectException
     */
    public function __destruct()
    {
        $this->getAdapter() and $this->getAdapter()->close();
    }


    ################### transaction ####################

    /**
     * @return void
     * @throws RedisConnectException
     */
    public function beginTransaction()
    {
        $this->getAdapter()->multi();
    }

    /**
     * @return void
     * @throws RedisConnectException
     */
    public function rollback()
    {
        $this->getAdapter()->discard();
    }

    /**
     * @return void
     * @throws RedisConnectException
     */
    public function commit()
    {
        $this->getAdapter()->exec();
    }

    ################### complex structure ####################

    static $_instances = [];

    public function getHash(string $name): Hash
    {
        if (!isset(self::$_instances[$name])) {
            self::$_instances[$name] = new Hash($name, $this);
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
            self::$_instances[$name] = new Lists($name, $this);
        }
        return self::$_instances[$name];
    }

    /**
     * 获取集合
     * @param string $name
     * @return Set
     */
    public function getSet(string $name): Set
    {
        if (!isset(self::$_instances[$name])) {
            self::$_instances[$name] = new Set($name, $this);
        }
        return self::$_instances[$name];
    }
}