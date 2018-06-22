<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:46
 */
declare(strict_types=1);


namespace driphp\core;

use driphp\Component;
use tests\core\RequestTest;

/**
 * Class Request 请求类
 *
 * http://localhost/php-projes/driphp.online/public/index/index?hello=world
 * 'HTTP_HOST' => 'localhost',
 * 'SCRIPT_FILENAME' => '/Users/zhonghuanglin/workspace/php-projes/driphp.online/public/index.php',
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
 * @package driphp\core
 */
class Request extends Component
{

    private $module = '';
    private $controller = '';
    private $action = '';
    private $params = [];
    protected $commandArguments = [];
    private $headers = [];

    # browser language
    const LANG_ZH = 'zh';
    const LANG_ZH_CN = 'zh_CN';
    const LANG_ZH_TW = 'zh_TW';
    const LANG_ZH_HK = 'zh_HK';
    const LANG_ZH_SG = 'zh_SG';# 新加坡
    const LANG_ZH_MO = 'zh_MO';# 澳门
    const LANG_EN = 'en';
    const LANG_EN_US = 'en_US';

    /**
     * @see https://stackoverflow.com/questions/5483851/manually-parse-raw-multipart-form-data-data-with-php
     */
    protected function initialize()
    {
        # Gets options from the command line argument list
        DRI_IS_CLI and $this->commandArguments = getopt('p:');
        switch (DRI_REQUEST_METHOD) {
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

    /** @var string */
    private $_language = null;


    public function setLanguage(string $language)
    {
        $this->_language = $language;
    }

    /**
     * get language from client
     * @param bool $useCommon It will only return the top category if set to TRUE (e.g. en_US => en,en_GB => en zh_CN => zh ) .
     *                  But zh_TW/zh_HK is exception ( It's quit different from Simplified Chinese), it will be convert to zh_TW means traditional Chinese
     * @return string
     */
    public function language(bool $useCommon = false)
    {
        if ($this->_language === null) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                if (preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches) and !empty($matches[1])) {
                    $_lang = str_replace('-', '_', $matches[1]);
                    if ($useCommon) {
                        $topCategory = substr($_lang, 0, 2);
                        switch ($topCategory) {
                            # 中文简体以外统一繁体(台湾,香港)
                            case self::LANG_ZH:
                                if ($_lang === self::LANG_ZH_CN) {
                                    $_lang = self::LANG_ZH; # 大陆简体
                                } elseif ($_lang === self::LANG_ZH_SG) {
                                    $_lang = self::LANG_ZH; # 新加坡简体
                                } else {
                                    $_lang = self::LANG_ZH_TW;# TW,HK和MO默认为TW
                                }
                                break;
                            case self::LANG_EN:
                            default:
                                $_lang = $topCategory;
                                break;
                        }
                    }
                    $this->_language = $_lang;
                }
            } else {
                $this->_language = 'en';
            }
        }
        return $this->_language;
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
            return $_REQUEST['_method'] ?? DRI_REQUEST_METHOD;
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
        if (DRI_IS_CLI) {
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
        # 截去query部分
        if ($pos = strpos($pathInfo, '?')) {
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
        if (DRI_LOAN_BALANCE_ON) return $_SERVER['REMOTE_ADDR'] ?? '';
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
        return DRI_IS_CLI ? '' : ($this->isHttps() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }

    /**
     * public access path ( it should be set manually if nginx balance load is put in use )
     * @return string
     */
    public function getPublicUrl(): string
    {
        return DRI_IS_CLI ? '' : $this->getHostUrl() . trim(dirname($_SERVER['SCRIPT_NAME'] . '\\/'));
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

    # browser type
    const AGENT_IE = 'ie';
    const AGENT_FIREFOX = 'firefox';
    const AGENT_EDGE = 'edge';
    const AGENT_CHROME = 'chrome';
    const AGENT_OPERA = 'opera';
    const AGENT_SAFARI = 'safari';
    const AGENT_UNKNOWN = 'unknown';

    /**
     * 获取浏览器类型
     * @return string
     */
    public function getBrowser()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return self::AGENT_UNKNOWN;
        }
        if ($agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') {
            if (strpos($agent = strtolower($agent), 'msie') !== false || strpos($agent, 'rv:11.0')) //ie11判断
                return self::AGENT_IE;
            elseif (strpos($agent, 'edge') !== false)
                return self::AGENT_EDGE;
            elseif (strpos($agent, 'firefox') !== false)
                return self::AGENT_FIREFOX;
            elseif (strpos($agent, 'chrome') !== false)
                return self::AGENT_CHROME;
            elseif (strpos($agent, 'opera') !== false)
                return self::AGENT_OPERA;
            elseif ((strpos($agent, 'chrome') == false) and strpos($agent, 'safari') !== false)
                return self::AGENT_SAFARI;
        }
        return self::AGENT_UNKNOWN;
    }

    /**
     * 获取浏览器版本(主版本号)
     * @return string
     */
    public static function getBrowserVer()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {    //当浏览器没有发送访问者的信息的时候
            return self::AGENT_UNKNOWN;
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs))
            return $regs[1];
        elseif (preg_match('/Edge\/(\d+)\..*/i', $agent, $regs))
            return $regs[1];
        elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs))
            return $regs[1];
        elseif (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs))
            return $regs[1];
        elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs))
            return $regs[1];
        elseif ((strpos($agent, 'Chrome') == false) and preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs))
            return $regs[1];
        else
            return self::AGENT_UNKNOWN;
    }


    /**
     * Cross Site Scripting (跨站脚本攻击)
     *
     * remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
     * this prevents some character re-spacing such as <java\0script>
     * note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
     *
     * @param string|array $val
     * @return string|array
     */
    public static function removeXss($val)
    {
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }

    /**
     * 判断是否是手机浏览器
     * @return bool
     */
    public static function isMobile()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) and preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i',
                strtolower($_SERVER['HTTP_USER_AGENT']))
        ) {
            return true;
        } elseif ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false)) {
            return true;
        }
        return false;
    }
}