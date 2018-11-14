<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 12:03
 */

namespace driphp\throws;


use driphp\KernelException;

class ConfigNotFoundException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 1002;
    }

}