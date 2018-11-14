<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 23/08/2018
 * Time: 10:14
 */

namespace driphp\throws;


use driphp\KernelException;

class CoreException extends KernelException
{

    public function getExceptionCode(): int
    {
        return 1008;
    }
}