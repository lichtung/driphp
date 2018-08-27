<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:55
 */
declare(strict_types=1);


namespace driphp\core;

use driphp\Component;

/**
 * Class Route
 *
 * @method Route getInstance(...$params) static
 *
 * @method mixed get(string $url, $rule) static
 * @method mixed post(string $url, $rule) static
 * @method mixed put(string $url, $rule) static
 * @method mixed delete(string $url, $rule) static
 * @method mixed any(string $url, $rule) static
 *
 * @package driphp\core
 */
class Route extends Component
{
    /** @var array 默认配置 */
    protected $config = [
        #  默认模块、控制器、操作
        'default_modules' => '',
        'default_controller' => 'index',
        'default_action' => 'index',

        'api_mode_on' => false,
        'api_modules_variable' => 'm',
        'api_controller_variable' => 'c',
        'api_action_variable' => 'a',
    ];
    /** @var array 虚拟主机与控制器的绑定 */
    private static $vhost2controller = [];

    /** @var Request $request 请求实例 */
    private $request = null;

    protected function initialize()
    {
    }

    /**
     * @param Request $request
     * @return null|array
     */
    public function parse(Request $request)
    {
        if ($this->config['api_mode_on'] ?? false) {

            $result = [];
            # API mode that with simple query parameter to determine the module, controller , action
            $m = $this->config['api_modules_variable'];
            $c = $this->config['api_controller_variable'];
            $a = $this->config['api_action_variable'];

            # module , controller, action , parameters
            isset($_GET[$m]) and $result['m'] = $_GET[$m];
            isset($_GET[$c]) and $result['c'] = $_GET[$c];
            isset($_GET[$a]) and $result['a'] = $_GET[$a];
            unset($_GET[$m], $_GET[$c], $_GET[$a]);
            $result['p'] = $_GET;
            if ($m = $_GET['m'] ?? false) {
                $c = ucfirst($_GET['c'] ?? '');
                return ["controller\\{$m}\\$c", $_GET['a'] ?? 'invoke'];
            } else {
                return ['controller\\' . $_GET['c']];
            }
        }

        $pathInfo = $request->getPathInfo();

        if (!empty(self::$vhost2controller)) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $controller = self::$vhost2controller[$host] ?? null;
            if (isset($controller)) {
                $action = trim($pathInfo, '/');
                return [$controller, $action ?: $this->config['default_action']];
            }
        }

        $method = strtolower(DRI_REQUEST_METHOD);
        # 静态式路由
        if (!empty(self::$staticRoute) and $rule = self::$staticRoute[$method . '-' . $pathInfo] ?? self::$staticRoute['any-' . $pathInfo] ?? false) {
            return $rule;
        } elseif ($wildcard = self::$wildcardRoute) {
            # 规则式路由
            foreach ($wildcard as $pattern => $rule) {
                if (strpos($pattern, $method) === 0) { # 检查请求方法
                    $pattern = substr($pattern, strlen($method) + 1);
                } elseif (strpos($pattern, 'any') === 0) {
                    $pattern = substr($pattern, 4);
                } else {
                    continue;
                }
                $matched = self::match($pathInfo, $pattern);
                if (isset($matched)) {
                    $request->setParams($matched);
                    return $rule;
                }
            }
        }
        list($modules, $controller, $action) = Request::parsePathInfo($pathInfo);
        $modules or $modules = $this->config['default_modules'];

        $request->setModule(is_array($modules) ? implode('/', $modules) : $modules);
        $request->setController($controller ?: $this->config['default_controller']);
        $request->setAction($action ?: $this->config['default_action']);
        return null;
    }

    ##################################### static method #############################################################


    private static $matchCache = [];

    /**
     * @param string $pathInfo
     * @param string $pattern 正则/规则式
     * @return null|array 匹配成功返回数组，里面包含匹配出来的参数；不匹配时返回null
     */
    public static function match(string $pathInfo, string $pattern)
    {
        if (strpos($pattern, '{') !== false) {
            if (isset(self::$matchCache[$pattern])) {
                list($compiledPattern, $params) = self::$matchCache[$pattern];
            } else {
                $params = [];
                $compiledPattern = preg_replace_callback('/\{[^\}]+?\}/', function ($matches) use (&$params) {
                    if ($name = $matches[0] ?? false) { # $matches[0]是完成的匹配 $matches[1]是第一个捕获子组的匹配（没有子组）
                        $params[trim($name, '{}')] = null;
                        return '([^/]+)';
                    } else {
                        return '';
                    }
                }, $pattern);
            }
        } else {
            $compiledPattern = $pattern; # 纯正则表达式
            $params = null;
        }
        $result = preg_match('#^' . $compiledPattern . '$#', rtrim($pathInfo, '/'), $matches);
        if ($result) { # 使用 '#' 代替开头和结尾的 '/'，可以忽略 $pattern 中的 "/"
            array_shift($matches);
            if (isset($params)) {
                $index = 0;
                foreach ($params as $name => &$val) {
                    $val = $matches[$index++] ?? null;
                }
            } else {
                $params = $matches;
            }
        } else {
            $params = null;
        }
        return $params;
    }

    /**
     * path path whose patterns like  "[modules]/controller/action"
     * @param string $path
     * @return array [module{array},controller,action]
     */
    public static function parsePath($path)
    {
        $path = explode('/', trim($path, '/'));
        if ($path) {
            $action = array_pop($path);
            if ($path) {
                $controller = array_pop($path);
                $modules = $path;
            }
        }
        return [$modules ?? [], $controller ?? '', $action ?? ''];
    }


    private static $currentGroup = '';
    private static $staticRoute = [];
    private static $wildcardRoute = [];

    final public static function group(string $name, callable $callback)
    {
        self::$currentGroup = $name;
        call_user_func($callback);
        self::$currentGroup = '';
    }

    /**
     * @param string $host 虚拟主机名称，如'blog.driphp.online'
     * @param string $controller 控制器名称，如 \controller\Blog::class | 'controller\Blog' | "controller\Blog"
     * @return void
     */
    public static function vhost(string $host, string $controller)
    {
        self::$vhost2controller[$host] = $controller;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (in_array($name, ['get', 'post', 'delete', 'put', 'any'])) {
            $url = $arguments[0];
            $rule = $arguments[1];
            self::$currentGroup and $url = '/' . self::$currentGroup . '/' . ltrim($url, '/');
            if (strpos($url, '{') === false and strpos($url, '[') === false) {
                self::$staticRoute[$name . '-' . $url] = $rule;
            } else {
                self::$wildcardRoute[$name . '-' . $url] = $rule;
            }
            return null;
        } else {
            return parent::__callStatic($name, $arguments);
        }
    }

}