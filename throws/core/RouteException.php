<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 19:15
 */
declare(strict_types=1);


namespace driphp\throws\core;


use driphp\DripException;
use Throwable;

/**
 * Class RouteException 路由异常
 * @package driphp\throws\core
 */
class RouteException extends DripException
{
    public function getExceptionCode(): int
    {
        return 20400;
    }
}