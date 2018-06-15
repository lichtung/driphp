<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 23:03
 */
declare(strict_types=1);

namespace driphp\core;


/**
 * Class Response 响应类
 * @see https://en.wikipedia.org/wiki/Same-origin_policy
 * @package driphp\core
 */
class Response
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
        204 => 'No Content',  # 资源有空表示
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently', # 资源的URI已被更新
        302 => 'Found',  // 1.1
        303 => 'See Other', # 其他（如，负载均衡）
        304 => 'Not Modified', # 资源未更改（缓存）
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request', # 指代坏请求（如，参数错误）
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Resource Not Found', # 资源不存在
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
        500 => 'Internal Server Error', # 通用错误响应
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable', # 服务端当前无法处理请求
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    ];
    /** @var array 头部列表 */
    private $headers = [];
    /** @var array 跨域域名列表 */
    private $accessControlAllowOrigins = [];
    /** @var int 响应状态码 */
    private $code = 200;
    /** @var string 输出内容 */
    protected $output = '';
    /** @var bool 设置不缓存头部 */
    protected $flagNoCache = false;
    /** @var bool 是否不限制跨域访问 */
    protected $flagCorsAll = false;

    /**
     * @param string $key
     * @param string $value
     * @return Response
     */
    public function setHeader(string $key, string $value): Response
    {
        if (strtolower($key) === 'access-control-allow-origin') {
            # 设置同源策略
            $this->accessControlAllowOrigins[] = $value;
        } else {
            $this->headers[$key] = $value;
        }
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


    /**
     * 不要缓存头部
     * @param bool $noCache
     * @return $this
     */
    public function nocache(bool $noCache = true)
    {
        $this->flagNoCache = $noCache;
        return $this;
    }

    /**
     * 输出内容
     * @return string
     */
    public function __toString(): string
    {
        if (!headers_sent()) {
            if (200 !== $this->code) {
                $message = self::CODE_MAP[$this->code] ?? 'Unknown';
                header("HTTP/1.1 {$this->code} {$message}");
            }
            if ($this->flagNoCache) {
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
            }
            # 设置跨域
            if ($this->flagCorsAll) {
                header('Access-Control-Allow-Origin:*');
            } else {
                if (in_array($origin = $_SERVER['HTTP_ORIGIN'] ?? '', $this->accessControlAllowOrigins))
                    header('Access-Control-Allow-Origin:' . $origin);
            }
            # 设置头部
            foreach ($this->headers as $key => $value) header("{$key}:{$value}");
        }
        if (empty($this->output))
            return $this->flush();
        else
            return $this->output;
    }


}