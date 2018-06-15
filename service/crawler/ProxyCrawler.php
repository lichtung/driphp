<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 17:33
 */

namespace driphp\service\crawler;

use driphp\Component;

/**
 * Class ProxyCrawler 代理爬虫
 * @package driphp\service\crawler
 */
abstract class ProxyCrawler extends Component
{
    protected function initialize()
    {
    }

    /**
     * 国内HTTP代理池
     * @return string
     */
    abstract public function internalHttpPool(): string;

    /**
     * 国内HTTPS代理池
     * @return string
     */
    abstract public function internalHttpsPool(): string;

    abstract public function internalCommonPool(): string;

    abstract public function internalGhostPool(): string;

    /**
     * 验证Http代理
     * @param string $ip
     * @param int $port
     */
    public function validateHttpProxy(string $ip, int $port)
    {


    }

//    /**
//     * 国外HTTP代理池
//     * @return string
//     */
//    abstract public function externalHttpPool(): string;
//    /**
//     * 国外HTTPS代理池
//     * @return string
//     */
//    abstract public function externalHttpsPool(): string;

}