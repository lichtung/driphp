<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 11:53
 */
declare(strict_types=1);


namespace driphp\throws\core\dispatch;


use driphp\throws\core\DispatchException;

/**
 * Class RouteInvalidException 非法路由异常
 * @package driphp\throws\core\dispatch
 */
class RouteInvalidException extends DispatchException
{

    public function getExceptionCode(): int
    {
        return 3106;
    }
}