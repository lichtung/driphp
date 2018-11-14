<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:29
 */

namespace driphp\throws;


use driphp\KernelException;

class ParametersInvalidException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 1007;
    }

}