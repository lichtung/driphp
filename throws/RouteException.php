<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 24/08/2018
 * Time: 15:29
 */

namespace driphp\throws;


use driphp\KernelException;

class RouteException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 1010;
    }
}