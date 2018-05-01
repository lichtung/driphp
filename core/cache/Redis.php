<?php
/**
 * User: linzhv@qq.com
 * Date: 24/04/2018
 * Time: 14:52
 */
declare(strict_types=1);


namespace sharin\core\cache;


use sharin\core\cache\redis\Lists;
use sharin\SharinException;
use sharin\throws\core\cache\RedisException;

class Redis extends Driver
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
     * @throws RedisException
     * @throws SharinException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->secret = sha1($this->config['secret']);
        $this->handler();
    }

    /**
     * @return Redis
     */
    public static function getInstance(): Redis
    {
        static $instance = null;
        $instance or $instance = new self();
        return $instance;
    }

    /**
     * @return \Redis
     * @throws RedisException
     * @throws SharinException
     */
    public function handler(): \Redis
    {
        if (!$this->handler) {
            if (!extension_loaded('redis')) throw new SharinException('php-redis is required');
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

            if ($error) throw new RedisException($error);
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
    public function set(string $key, $value, int $ttl = 3600): void
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

    public function delete(string $key): void
    {
        $this->handler->delete($key); # Number of keys deleted.
    }

    public function clean(): void
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

//    public function getHash(string $name): Hash
//    {
//        if (!isset(self::$_instances[$name])) {
//            self::$_instances[$name] = new Hash($name, $this->handler);
//        }
//        return self::$_instances[$name];
//    }

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

//    public function getSet(string $name): Set
//    {
//        if (!isset(self::$_instances[$name])) {
//            self::$_instances[$name] = new Set($name, $this->handler);
//        }
//        return self::$_instances[$name];
//    }
}