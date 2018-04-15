<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 23:03
 */
declare(strict_types=1);


namespace sharin\core;


use sharin\Component;

/**
 * Class Response
 *
 * 跨域资源共享(Cross-Origin Resource Sharing)是指当前域（domain）的资源(web service)被其他域请求的机制，基于同域安全策略的浏览器会
 * 禁止这种跨域操作（注意是客户端检测返回的头部作出的限制）
 *
 *
 *
 * set 'Access-Control-Allow-Origin' to realize cross-domain
 * Chrome will present message like below when using ajax to get data from other website:
 * XMLHttpRequest cannot load http://server.runoob.com/server.php. No 'Access-Control-Allow-Origin'
 * header is present on the requested resource.Origin 'http://client.runoob.com' is therefore not
 * allowed access.
 *
 * - 允许单个域名访问 :   header('Access-Control-Allow-Origin:http://client.runoob.com');
 * - 允许所有域名访问:    header('Access-Control-Allow-Origin:*');
 * - 允许多个域名访问 :
 *      $origin =$_SERVER['HTTP_ORIGIN'] ?? '';
 *      $allow_origin = [ 'http://client1.runoob.com', 'http://client2.runoob.com' ];
 *      if(in_array($origin, $allow_origin))  if(in_array($origin, $allow_origin)){
 *
 *
 * @see https://en.wikipedia.org/wiki/Same-origin_policy
 *
 * @method Response getInstance(string $index = '') static
 * @package sharin\core
 */
class Response extends Component
{
    const CODE_MAP = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Resource Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    ];
    protected $config = [
        'cors_all' => false, # 允许全域访问
        'cors_list' => [],
    ];
    /**
     * @var array 头部列表
     */
    private $headers = [];
    /**
     * @var array 跨域域名列表
     */
    private $accessControlAllowOrigins = [];
    /**
     * @var int  状态码
     */
    private $code = 200;
    /**
     * @var string 输出内容
     */
    private $output = '';

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Response
     */
    public function setHeader(string $key, string $value): Response
    {
        if (strtolower($key) === 'access-control-allow-origin') {
            $this->accessControlAllowOrigins[] = $value;
        } else {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * 设置同源策略
     * @param string $origin 访问域名，如 http://www.example.com http://www.example.com:80(视浏览器实现而定)
     * @return $this
     */
    public function setAllowOrigin(string $origin = '*'): Response
    {
        $this->setHeader('Access-Control-Allow-Origin', $origin);
        return $this;
    }

    /**
     * 设置响应状态码
     * @param int $code
     * @return Response
     */
    public function setStatus(int $code): Response
    {
        $this->code = $code;
        return $this;
    }


    /**
     * ob缓存默认是打开的，所以level默认等于1
     * @return int
     */
    public function getLevel(): int
    {
        return ob_get_level();
    }

    protected function __construct(string $index = '')
    {
        parent::__construct($index);
        ob_start(null);
    }

    /**
     * 打开输出控制缓冲
     * 当输出缓冲激活后，脚本将不会输出内容（除http标头外），需要输出的内容被存储在内部缓冲区中。
     * 输出缓冲区是可堆叠的，这即意谓着，当有一个 ob_start() 是活跃的时， 你可以调用另一个 ob_start() 。 只要确保又正确调用了 ob_end_flush() 恰当的次数即可。
     *
     * @return bool
     */
    public function startBuffer(): bool
    {
        return ob_start();
    }

    /**
     * 刷出缓冲区内容,并结束该层缓冲区
     * @return string 返回该层缓冲区内容
     */
    public function flush(): string
    {
        $this->output .= $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * 清空之前所有的输出，清理并关闭所有的缓冲区
     * @return Response
     */
    public function clean(): Response
    {
        $this->output = '';
        $level = ob_get_level();
        while ($level--) ob_end_clean();
        ob_start();
        return $this;
    }

    public function nocache()
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    /**
     * 设置输出内容为JSON
     * @param array $data
     * @param int $options
     * @return Response
     */
    public function json(array $data, $options = 0): Response
    {
        $this->setHeader('Content-Type', 'application/json;charset=utf-8');
        $this->output = json_encode($data, $options);
        exit(0);
    }

    /**
     * 设置JSONP输出
     * @param array $data
     * @return void
     */
    public function jsonp(array $data)
    {
        $this->output = ($_GET['callback'] ?? 'callback') . '(' . json_encode($data) . ')';
        exit(0);
    }

    /**
     * 立即进行跳转
     * @param string $url
     * @param int $time
     * @param string $message
     * @return void
     */
    public function redirect(string $url, int $time = 0, string $message = ''): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = Request::getInstance()->getPublicUrl() . str_replace(["\n", "\r"], ' ', $url);
        }
        $message or $message = "Redirection after {$time} seconds'{$url}'！";
        if (headers_sent()) {
            $this->output = '';
            exit("<meta http-equiv='Refresh' content='{$time};URL={$url}'>{$message}");
        }
        if (0 === $time) {
            $this->setHeader('Location', $url);
        } else {
            $this->setHeader('refresh', "{$time};url={$url}");
            $this->output = $message;
        }
        exit(0);
    }

    public function render(View $view): void
    {
        echo $view;
        exit(0);
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

    public function __destruct()
    {
        echo $this;
    }

    public function __toString(): string
    {
        # 设置状态码
        $message = self::CODE_MAP[$this->code] ?? 'Unknown';
        header("HTTP/1.1 {$this->code} {$message}");
        # 设置跨域
        if ($this->config['cors_all']) {
            header('Access-Control-Allow-Origin:*');
        } else {
            in_array($origin = $_SERVER['HTTP_ORIGIN'] ?? '', $this->accessControlAllowOrigins) and header('Access-Control-Allow-Origin:' . $origin);
        }
        # 设置头部
        foreach ($this->headers as $key => $value) header("{$key}:{$value}");
        return $this->output;
    }

    # browser type
    const AGENT_IE = 'ie';
    const AGENT_FIRFOX = 'firefox';
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
                return self::AGENT_FIRFOX;
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

}