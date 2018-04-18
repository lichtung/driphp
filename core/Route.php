<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:55
 */
declare(strict_types=1);


namespace sharin\core;

use sharin\Component;

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
 * @package sharin\core
 */
class Route extends Component
{
    /** @var array 默认配置 */
    protected $config = [
        //------------------------
        //For URL route
        //------------------------
        'route_on' => true, # main switch
        //static route
        'static_route_on' => true,
        // wildcard route will be parsed to regular expression and this idea is come from the framework of CodeIgniter
        'wildcard_route_on' => true,

        //------------------------
        //For URL parser
        //------------------------
        //API模式，直接使用$_GET
        'api_mode_on' => false,
        //API模式 对应的$_GET变量名称
        'api_modules_variable' => '_m',
        'api_controller_variable' => '_c',
        'api_action_variable' => '_a',

        //普通模式
        'masquerade_tail' => '.html',

        'default_modules' => '',
        'default_controller' => 'index',
        'default_action' => 'index',

    ];
//    private static $rules = [];

    /**
     * @var Request
     */
    private $request = null;

    /**
     * @param Request $request
     * @return bool|mixed|object
     */
    public function parse(Request $request)
    {
        $this->request = $request;
        $pathInfo = $request->getPathInfo();
        $method = strtolower(SR_REQUEST_METHOD);
        # 静态式路由
        if ($this->config['static_route_on'] and $rule = self::$staticRoute[$method . '-' . $pathInfo] ?? self::$staticRoute['any-' . $pathInfo] ?? false) {
            return $rule;
        } elseif ($this->config['wildcard_route_on'] and $wildcard = self::$wildcardRoute) {
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
        $this->request->setModule(is_array($modules) ? implode('/', $modules) : $modules);
        $this->request->setController($controller ?: $this->config['default_controller']);
        $this->request->setAction($action ?: $this->config['default_action']);
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

    public static function __callStatic(string $name, array $arguments)
    {
        if (in_array($name, ['get', 'post', 'delete', 'put', 'any'])) {
            $url = $arguments[0];
            $rule = $arguments[1];
            self::$currentGroup and $url = '/' . self::$currentGroup . '/' . ltrim($url, '/');
            if (strpos($url, '{') !== false and strpos($url, '[') !== false) {
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