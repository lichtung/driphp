<?php
/**
 * User: linzhv@qq.com
 * Date: 28/04/2018
 * Time: 22:05
 */
declare(strict_types=1);


namespace sharin\core\response;


use sharin\core\Request;
use sharin\core\Response;

class Redirect extends Response
{

    /**
     * 立即进行跳转
     * @param string $url
     * @param int $time
     * @param string $message
     * @return void
     */
    public function __construct(string $url, int $time = 0, string $message = '')
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
    }
}