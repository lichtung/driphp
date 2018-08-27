<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 15:01
 */
declare(strict_types=1);


namespace driphp\core;


use driphp\Component;

/**
 * Class Cookie
 *
 * @method Cookie getInstance(array $config = []) static
 *
 * @package driphp\core
 */
class Cookie extends Component
{

    protected $config = [
        // cookie 名称前缀
        'prefix' => '',
        // cookie 保存时间
        'expire' => 0,
        // cookie 保存路径
        'path' => '/',
        // cookie 有效域名
        'domain' => '',
        //  cookie 启用安全传输
        'secure' => false,
        // httponly设置
        'httponly' => '',
    ];
    /** @var array $source */
    private $source;

    protected function initialize()
    {
        empty($this->config['httponly']) or ini_set('session.cookie_httponly', '1');

        $this->source = &$_COOKIE;
    }

    /**
     * 判断Cookie数据
     * @param string $name cookie名称
     * @return bool
     */
    public function has($name): bool
    {
        return isset($this->source[$name]);
    }

    /**
     * Cookie 设置、获取、删除
     * @param string $name cookie名称
     * @param string|int $value cookie值
     * @param int|array $option 配置参数,如果是int表示cookie有效期,如果是array,则表示setcookie参数数组
     *
     * @return void
     */
    public function set($name, $value = '', $option = null)
    {
        # 参数设置(会覆盖黙认设置)
        if (isset($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            }
            $this->config = array_merge($this->config, array_change_key_case($option));
        }

        // 设置cookie
        if (is_array($value)) {
            array_walk($value, function (&$val) {
                empty($val) or $val = urlencode($val);
            });
            $value = json_encode($value);
        }
        $expire = !empty($this->config['expire']) ? time() + intval($this->config['expire']) : 0;
        setcookie($name, $value, $expire,
            (string)$this->config['path'],
            (string)$this->config['domain'],
            (bool)$this->config['secure'],
            (bool)$this->config['httponly']);
        $this->source[$name] = $value;
    }

    /**
     * Cookie获取
     * @param string $name cookie名称
     * @return string|null cookie不存在时返回null
     */
    public function get($name)
    {
        if (isset($this->source[$name])) {
            $value = $this->source[$name];
            $value = substr($value, 6);
            $value = json_decode($value, true);
            array_walk($value, function (&$val) {
                empty($val) or $val = urldecode($val);
            });
            return $value;
        } else {
            return null;
        }
    }

    /**
     * Cookie删除
     * @param string $name cookie名称
     * @return void
     */
    public function delete($name)
    {
        setcookie($name, '', time() - 3600,
            (string)$this->config['path'],
            (string)$this->config['domain'],
            (bool)$this->config['secure'],
            (bool)$this->config['httponly']);
        // 删除指定cookie
        unset($this->source[$name]);
    }

    /**
     * Cookie清空
     * @return void
     */
    public function clear()
    {
        // 清除指定前缀的所有cookie
        if ($this->source) {
            // 如果前缀为空字符串将不作处理直接返回
            foreach ($this->source as $key => $val) {
                setcookie(
                    $key,
                    '',
                    $_SERVER['REQUEST_TIME'] - 3600,
                    (string)$this->config['path'],
                    (string)$this->config['domain'],
                    (bool)$this->config['secure'],
                    (bool)$this->config['httponly']
                );
                unset($this->source[$key]);
            }
        }
    }
}