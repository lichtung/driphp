<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:29
 */

namespace driphp\throws;


use driphp\DripException;

class ParametersInvalidException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1007;
    }

}