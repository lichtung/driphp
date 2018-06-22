<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 19:15
 */
declare(strict_types=1);


namespace driphp\throws\core;


use driphp\DriException;
use Throwable;

/**
 * Class RouteException 路由异常
 * @package driphp\throws\core
 */
class RouteException extends DriException
{
    public function getExceptionCode(): int
    {
        return 20400;
    }
}