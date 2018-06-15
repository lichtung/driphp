<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/22 0022
 * Time: 11:33
 */
declare(strict_types=1);


namespace driphp\core\cache;

use driphp\core\FileSystem;
use driphp\throws\core\cache\CacheException;

/**
 * Class File 文件缓存驱动
 * @package driphp\core\cache
 */
class File extends Driver
{
    protected $config = [
        'cache_path' => SR_PATH_RUNTIME . 'cache/',
    ];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if (!is_dir($this->config['cache_path'])) mkdir($this->config['cache_path'], 0700, true);
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
        $contents = [
            'k' => $key,
            'v' => $value,
            't' => time(),# time
            'ttl' => $ttl,#
        ];
        if (!FileSystem::write($path = $this->_parsePath($key), serialize($contents))) {
            throw new CacheException("Writing cache to '$path' failed", -1);
        }
    }

    /**
     * @param string $key
     * @param null $replace
     * @return mixed|null
     * @throws \driphp\throws\io\FileNotFoundException
     * @throws \driphp\throws\io\FileReadException
     */
    public function get(string $key, $replace = null)
    {
        if (!is_file($file = $this->_parsePath($key))) {
            return $replace;
        }
        $data = FileSystem::read($file);
        if (false === $data) {
            return $replace;
        }
        $data = unserialize($data);

        if ($data['ttl'] > 0 && time() > $data['t'] + $data['ttl']) {
            unlink($file);
            return $replace;
        }
        return $data['v'];
    }

    /**
     * @param string $key
     * @return void
     * @throws CacheException
     */
    public function delete(string $key): void
    {
        if (is_file($file = $this->_parsePath($key))) {
            if (!unlink($file)) throw new CacheException("cache '$key' remove failed");
        } else {
            throw new CacheException("cache '$key' not exist");
        }
    }

    /**
     * @return void
     * @throws CacheException
     */
    public function clean(): void
    {
        if (!FileSystem::rmdir($this->config['cache_path'])) {
            throw new CacheException("cache remove failed");
        }
    }

    private function _parsePath(string $key)
    {
        return $this->config['cache_path'] . md5($key);
    }

}