<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:55
 */
declare(strict_types=1);


namespace sharin\core;


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
        'static_route_on' => false,
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
     * @return bool
     * @throws \sharin\throws\core\dispatch\ActionAccessException
     * @throws \sharin\throws\core\dispatch\ActionNotFoundException
     * @throws \sharin\throws\core\dispatch\ControllerNotFoundException
     * @throws \sharin\throws\core\dispatch\ModulesNotFoundException
     * @throws \sharin\throws\core\dispatch\ParameterNotFoundException
     */
    public function dispatch(Request $request)
    {
        $this->request = $request;
        $pathinfo = $request->getPathInfo();
        # 静态式路由
        if ($this->config['static_route_on'] and $rule = $this->config['static_route_rules'][$pathinfo] ?? false) {
            return $this->handleRoute($rule);
        }
        # 规则式路由
        if ($this->config['wildcard_route_on'] and $wildcard = $this->config['wildcard_route_rules']) {
            foreach ($wildcard as $pattern => $rule) {
                $matched = self::match($pathinfo, $pattern);
                if (isset($matched)) return $this->handleRoute($rule, $matched);
            }
        }
        return $this->handleRoute(Request::parsePathInfo($pathinfo));
    }

    /**
     * @param $rule
     * @param array $extra
     * @return bool|mixed
     * @throws \sharin\throws\core\ClassNotFoundException
     * @throws \sharin\throws\core\dispatch\ActionAccessException
     * @throws \sharin\throws\core\dispatch\ActionNotFoundException
     * @throws \sharin\throws\core\dispatch\ControllerNotFoundException
     * @throws \sharin\throws\core\dispatch\ModulesNotFoundException
     * @throws \sharin\throws\core\dispatch\ParameterNotFoundException
     */
    private function handleRoute($rule, array $extra = [])
    {
        if (is_array($rule)) {
            empty($rule[0]) and $rule[0] = $this->config['default_modules'];
            $this->request->setModule(is_array($rule[0]) ? implode('/', $rule[0]) : $rule[0]);
            $this->request->setController($rule[1] ?? $this->config['default_controller']);
            $this->request->setAction($rule[2] ?? $this->config['default_action']);
            $this->request->setParams($extra);
            return Dispatcher::dispatch($this->request);
        } elseif (is_string($rule)) {
            if (strpos($rule, 'http') === 0) {
                Response::getInstance()->redirect($rule); # 立即重定向
            } else {
                if (class_exists($rule)) {
                    $instance = Kernel::factory($rule, [$this->request]);
                    return $instance;
                } else {
                    $rule = self::parsePath($rule);
                }
            }
        } elseif (is_callable($rule)) {
            return call_user_func_array($rule, $extra);
        } else {
            throw new RouteException('Invalid Route', $rule);
        }
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
        } # dumpout($pattern,);
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



//    public function handleRouteRule($rule, array $extra = []): RoutePacket
//    {
//        switch ($routeType = gettype($rule)) {
//            case SR_TYPE_STR:
//                if (strpos($rule, 'http') === 0) {
//                    # redirect to a new url that begin with http or https protocol
//                    $_REQUEST and $rule .= (strpos($rule, '?') ? '&' : '?') . http_build_query($_REQUEST);
//                    return $this->createRoutePacket('', '', '', $extra, $rule);
//                } else {
//                    list($module, $controller, $action) = Request::parsePathInfo($rule);
//                    return $this->createRoutePacket($module, $controller, $action, $extra, '');
//                }
//            case SR_TYPE_ARRAY:
//                return $this->createRoutePacket($rule[0] ?? null, $rule[1] ?? null,
//                    $rule[2] ?? null, empty($rule[3]) ? $extra : array_merge($rule[3], $extra), '');
//            case SR_TYPE_OBJ:
//                if (!is_callable($rule)) throw new RouteException('invalid route object, expect to be callable');
//                $result = call_user_func_array($rule, [
//                    'extra' => $extra,
//                    'context' => $this,
//                ]);
//                if (!$result instanceof RoutePacket) throw new RouteException('invalid route callable, expect execute result to be an instance of RoutePacket');
//                return $result;
//            default:
//                throw new RouteException("invalid route type : $routeType");
//        }
//    }


//    public static function module(string $groupName, callable $callable): void
//    {
//
//    }

}