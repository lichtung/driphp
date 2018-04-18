<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:46
 */
declare(strict_types=1);


namespace sharin\core;


use sharin\Component;
use tests\core\RequestTest;

/**
 * Class Request
 *
 * http://localhost/php-projes/sharin.online/public/index/index?hello=world
 * 'HTTP_HOST' => 'localhost',
 * 'SCRIPT_FILENAME' => '/Users/zhonghuanglin/workspace/php-projes/sharin.online/public/index.php',
 * 'REDIRECT_QUERY_STRING' => 'hello=world',
 * 'QUERY_STRING' => 'hello=world',
 * 'REQUEST_URI' => '/php-projes/sharin.online/public/index/index?hello=world',
 * 'SCRIPT_NAME' => '/php-projes/sharin.online/public/index.php',
 * 'PATH_INFO' => '/index/index',
 * 'PHP_SELF' => '/php-projes/sharin.online/public/index.php/index/index',
 *
 * http://localhost/php-projes/sharin.online/public/index.php/index/index?hello=world
 * 'HTTP_HOST' => 'localhost',
 * 'SCRIPT_FILENAME' => '/Users/zhonghuanglin/workspace/php-projes/sharin.online/public/index.php',
 * 'QUERY_STRING' => 'hello=world',
 * 'REQUEST_URI' => '/php-projes/sharin.online/public/index.php/index/index?hello=world',
 * 'SCRIPT_NAME' => '/php-projes/sharin.online/public/index.php',
 * 'PATH_INFO' => '/index/index',
 * 'PHP_SELF' => '/php-projes/sharin.online/public/index.php/index/index',
 *
 * http://locale.sharin.online/index/index?hello=world
 * 'HTTP_HOST' => 'locale.sharin.online',
 * 'SCRIPT_FILENAME' => '/Users/zhonghuanglin/workspace/php-projes/sharin.online/public/index.php',
 * 'REDIRECT_QUERY_STRING' => 'hello=world',
 * 'REQUEST_METHOD' => 'GET',
 * 'QUERY_STRING' => 'hello=world',
 * 'REQUEST_URI' => '/index/index?hello=world',
 * 'SCRIPT_NAME' => '/index.php',
 * 'PATH_INFO' => '/index/index',
 * 'PHP_SELF' => '/index.php/index/index',
 *
 * http://locale.sharin.online/index.php/index/index?hello=world
 * 'HTTP_HOST' => 'locale.sharin.online',
 * 'SCRIPT_FILENAME' => '/Users/zhonghuanglin/workspace/php-projes/sharin.online/public/index.php',
 * 'QUERY_STRING' => 'hello=world',
 * 'REQUEST_URI' => '/index.php/index/index?hello=world',
 * 'SCRIPT_NAME' => '/index.php',
 * 'PATH_INFO' => '/index/index',
 * 'PHP_SELF' => '/index.php/index/index',
 * @method Request getInstance(...$params) static
 * @package sharin\core
 */
class Request extends Component
{

    private $module = '';
    private $controller = '';
    private $action = '';
    private $params = [];
    protected $commandArguments = [];
    private $headers = [];

    /**
     * Request constructor.
     *
     * @see @see https://stackoverflow.com/questions/5483851/manually-parse-raw-multipart-form-data-data-with-php
     *
     * @param string $connect
     */
    protected function __construct(string $connect = '')
    {
        parent::__construct($connect);
        # Gets options from the command line argument list
        SR_IS_CLI and $this->commandArguments = getopt('p:');
        switch (SR_REQUEST_METHOD) {
            case '':# client script
                break;
            case 'GET': # Get resource from server(one or more)
                break;
            case 'POST': # Create resource
                break;
            case 'PUT': # Update resource with full properties
            case 'PATCH': # Update resource with some properties
            case 'DELETE': # Delete resource
                if ($_input = $this->rawInput()) {
                    parse_str($_input, $_request_data);
                    $_request_data and $_REQUEST = array_merge($_REQUEST, $_request_data);
                }
                break;

        }
    }

    /**
     * 兼容put,delete,patch方法
     * @return string
     */
    public function getRequestMethod()
    {
        if ($method = $this->headers['X-HTTP-METHOD-OVERRIDE'] ?? false) {
            return strtoupper($method);
        } else {
            return $_REQUEST['_method'] ?? SR_REQUEST_METHOD;
        }
    }

    /**
     * 获取请求的原始数据的流输入
     * PHP 5.6 之前 php://input 打开的数据流只能读取一次
     * @see http://php.net/manual/zh/wrappers.php.php
     * @return int|string
     */
    public function rawInput(): string
    {
        static $raw = null;
        isset($raw) or $raw = file_get_contents('php://input') ?: '';
        return $raw;
    }

    /**
     * @return array
     */
    public function getCommandArguments(): array
    {
        return $this->commandArguments;
    }

    /**
     * 非80端口时$_SERVER['HTTP_HOST'] 带上端口号，如 sharin.online:8080 ,等效于 HTTP_HOST = SERVER_NAME : SERVER_PORT
     * @return string
     */
    public function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? "{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}";
    }

    /**
     * 获取请求路径
     * 注：
     *  - pathinfo中不能有点号"."
     *  - REQUEST_URI 只有apache才支持
     * @return string
     */
    public function getPathInfo(): string
    {
        if (SR_IS_CLI) {
            $pathInfo = $this->commandArguments['p'] ?? '';
        } else {
            if (empty($_SERVER['PATH_INFO'])) {
                $pos = strpos($_SERVER['PHP_SELF'], '/index.php');
                $path = substr($_SERVER['PHP_SELF'], 0, $pos);
                $requestURL = $_SERVER['REQUEST_URI'] ?? $_SERVER['REDIRECT_URL'] ?? ''; # .htaccess 重定向产生了 REDIRECT_URL
                $pathInfo = substr($requestURL, strlen($path));
            } else {
                $pathInfo = $_SERVER['PATH_INFO'] ?? '';
            }
        }
        $pathInfo = trim($pathInfo, '.');
        if ($pos = strpos($pathInfo, '.')) {
            # 删除伪后缀 如 .html .htm .jsp(假透了)
            $pathInfo = substr($pathInfo, 0, $pos);
        }
        return $pathInfo ?: '/';
    }

    /**
     * 获取客户端IP
     *
     * 区别：
     * - REMOTE_ADDR不可以显式的伪造，虽然可以通过代理将ip地址隐藏，但是这个地址仍然具有参考价值，因为它就是与你的服务器实际连接的ip地址。
     * - HTTP_CLIENT_IP/HTTP_X_FORWARDED_FOR 可以通过http header来伪造，但并不意味着它们一无是处。生产环境中很多服务器隐藏在负载均衡节点后面，你通过REMOTE_ADDR只能获取到负载均衡节点的ip地址
     *
     * 总结：
     *  负载均衡下 HTTP_CLIENT_IP/HTTP_X_FORWARDED_FOR 是真实可信的，因为它是负载均衡节点告诉你的而不是客户端
     *  当你的服务器直接暴露在客户端前面的时候，请不要信任这两种读取方法，只需要读取REMOTE_ADDR就行了
     *
     * @return string
     */
    public function getClientIP(): string
    {
        if (SR_LOAN_BALANCE_ON) return $_SERVER['REMOTE_ADDR'] ?? '';
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? '';
    }


    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string $module
     * @return $this
     */
    public function setModule(string $module): Request
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return $this
     */
    public function setController(string $controller): Request
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction(string $action): Request
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 获取输入参数
     * @param string $index
     * @return mixed|null
     */
    public function getParam(string $index)
    {
        return $this->params[$index] ?? null;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params): Request
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 确定客户端发起的请求是否基于SSL协议
     * @return bool
     */
    public static function isHttps()
    {
        return (isset($_SERVER['HTTPS']) and ('1' == $_SERVER['HTTPS'] or 'on' == strtolower($_SERVER['HTTPS']))) or
            (isset($_SERVER['SERVER_PORT']) and ('443' == $_SERVER['SERVER_PORT']));
    }

    public function getHostUrl(): string
    {
        return SR_IS_CLI ? '' : ($this->isHttps() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }

    /**
     * public access path ( it should be set manually if nginx balance load is put in use )
     * @return string
     */
    public function getPublicUrl(): string
    {
        return SR_IS_CLI ? '' : $this->getHostUrl() . trim(dirname($_SERVER['SCRIPT_NAME'] . '\\/'));
    }

    /**
     * 解析pathinfo
     * @see RequestTest::testParsePathInfo()
     * @param string $pathInfo 请求的pathinfo路径
     * @param string $mm 模块之间的连接符
     * @param string $mc 模块和控制器之间的连接符
     * @param string $ca 控制器和操作之间的连接符
     * @return array 返回MCA数组：0元素代表模块列表(array) 1元素代表控制器 2元素代表操作
     */
    public static function parsePathInfo(string $pathInfo, string $mm = '/', string $mc = '/', string $ca = '/'): array
    {
        $parsed = [[], '', ''];
        if ($pathInfo = trim($pathInfo, ' /')) {
            $capos = strrpos($pathInfo, $ca);
            if (false === $capos) {
                $parsed[2] = $pathInfo;
            } else {
                $parsed[2] = substr($pathInfo, $capos + strlen($ca));

                //CA存在衔接符 则说明一定存在控制器
                $mcaLength = strlen($pathInfo);
                $mcPart = substr($pathInfo, 0, $capos - $mcaLength);

                if (strlen($pathInfo)) {
                    $mcPosition = strrpos($mcPart, $mc);
                    if (false === $mcPosition) {
                        //不存在模块
                        if (strlen($mcPart)) {
                            //全部是控制器的部分
                            $parsed[1] = $mcPart;
                        }   //没有控制器部分，则使用默认的
                    } else {
                        //截取控制器的部分
                        $parsed[1] = substr($mcPart, $mcPosition + strlen($mc));

                        //既然存在MC衔接符 说明一定存在模块
                        $mPart = substr($mcPart, 0, $mcPosition - strlen($mcPart));//以下的全是模块部分的字符串
                        if (strlen($mPart)) {
                            if (false === strpos($mPart, $mm)) {
                                $parsed[0] = [$mPart];
                            } else {
                                $parsed[0] = explode($mm, $mPart);
                            }
                        }
                    }
                }
            }
        }
        return $parsed;
    }

}