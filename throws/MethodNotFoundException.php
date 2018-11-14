<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:28
 */

namespace driphp\throws;


use driphp\KernelException;

class MethodNotFoundException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 1006;
    }

}