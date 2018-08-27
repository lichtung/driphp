<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 24/08/2018
 * Time: 15:29
 */

namespace driphp\throws;


use driphp\DripException;

class RouteException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1010;
    }
}