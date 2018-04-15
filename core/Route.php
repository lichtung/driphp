<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:55
 */
declare(strict_types=1);


namespace sharin\core;

use Closure;
use sharin\Component;
use sharin\Kernel;
use sharin\throws\core\RouteException;
use tests\core\RouteTest;

/**
 * Class Route
 *
 * @method Route getInstance(...$params) static
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
        'static_route_rules' => [],
        // wildcard route will be parsed to regular expression and this idea is come from the framework of CodeIgniter
        'wildcard_route_on' => false,
        'wildcard_route_rules' => [],

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
        $pathinfo = $request->getPathInfo();
        # 静态式路由
        if ($this->config['static_route_on'] and $rule = $this->config['static_route_rules'][$pathinfo] ?? false) {
            return $rule;
        } elseif ($this->config['wildcard_route_on'] and $wildcard = $this->config['wildcard_route_rules']) {
            # 规则式路由
            foreach ($wildcard as $pattern => $rule) {
                $matched = self::match($pathinfo, $pattern);
                if (isset($matched)) {
                    $request->setParams($matched);
                    return $rule;
                }
            }
        }
        list($modules, $controller, $action) = Request::parsePathInfo($pathinfo);
        $modules or $modules = $this->config['default_modules'];
        $this->request->setModule(is_array($modules) ? implode('/', $modules) : $modules);
        $this->request->setController($controller ?: $this->config['default_controller']);
        $this->request->setAction($action ?: $this->config['default_action']);
        return null;
    }

    ##################################### static method #############################################################

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

    /**
     * 执行匹配过程
     * @see RouteTest::testMatch()
     * @param string $pathinfo
     * @param string $pattern 正则/规则式
     * @return null|array 匹配成功返回数组，里面包含匹配出来的参数；不匹配时返回null
     */
    public static function match(string $pathinfo, string $pattern)
    {
        $positionBrace = strpos($pattern, '{');
        $positionSquare = strpos($pattern, '[');
        if ($positionBrace !== false or $positionSquare !== false) {
            $pattern = str_replace(
                ['{any}', '{num}', '/[any]', '/[num]'], # 花括号表示参数是必须要有的，中括号表示可选
                ['([^/]+)', '([0-9]+)', '(/[^/]+)?', '(/[0-9]+)?'], # 可选的会把前面的"/"一并带走
                $pattern);  //$pattern = preg_replace('/\[.+?\]/','([^/\[\]]+)',$pattern);//non-greediness mode
        } # dumpout($pattern);
        if (preg_match('#^' . $pattern . '$#', rtrim($pathinfo, '/'), $matches)) { # 使用 '#' 代替开头和结尾的 '/'，可以忽略 $pattern 中的 "/"
            array_shift($matches);
            array_walk($matches, function (&$item) {
                $item = ltrim($item, '/');
                is_numeric($item) and $item = (int)$item;
            });
        } else {
            $matches = null;
        }
        return $matches;
    }

}