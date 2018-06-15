<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 08:50
 */
declare(strict_types=1);


namespace driphp\core;


use driphp\Component;

/**
 * Class Service Vendor服务类
 * @package driphp\core
 */
abstract class Service extends Component
{

    protected function initialize()
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

}