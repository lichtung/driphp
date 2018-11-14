<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 17:48
 */

namespace driphp\tests\service\crawler;


use driphp\core\Cache;
use driphp\library\NetTelnet;
use driphp\service\crawler\XiciProxyCrawler;
use driphp\tests\UnitTest;
use Exception;

class proxy
{
    private $proxy_ip;
    private $proxy_port;
    private $check_url;
    private $time_out;
    private $retry;

    public function __construct($proxy_ip, $proxy_port, $check_url = 'http://www.baidu.com/robots.txt', $time_out = 30, $retry = 2)
    {
        $this->proxy_ip = $proxy_ip;
        $this->proxy_port = $proxy_port;
        $this->check_url = $check_url;
        $this->time_out = $time_out;
        $this->retry = $retry;
    }

    public function check_proxy()
    {
        // 创建一个新cURL资源
        $ch = curl_init();
        // 设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $this->check_url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->time_out);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy_ip . ':' . $this->proxy_port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 抓取URL并把它传递给浏览器
        $i = 1;
        $result = false;
        while ($i <= $this->retry) {
            $result = curl_exec($ch);
            if ($result !== false && substr_count($result, 'User-agent: Baiduspider') >= 1) {
                $result = true;
                break;
            } else {
                $result = false;
            }
            ++$i;
        }

        // 关闭cURL资源，并且释放系统资源
        curl_close($ch);
        //成功返回boolean true, 失败返回boolean false
        return $result;
    }
}

class XiciTest extends UnitTest
{
    /**
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     */
    public function testDemo()
    {
        $proxyPool = Cache::getInstance()->get('proxy', function () {
            return XiciProxyCrawler::getInstance()->requestHttpPool();
        }, 3600);


        $count = 1;
        foreach ($proxyPool as $item) {
            list($ip, $port) = $item;
            echo "$ip:$port ";
            $res = (new proxy($ip, $port, 'http://www.baidu.com/robots.txt', 3))->check_proxy();
            echo "$res \n";
            if ($count++ > 5) die();
        }


        $this->assertTrue(true);
    }

}