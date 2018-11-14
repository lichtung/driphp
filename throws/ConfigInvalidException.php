<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:16
 */

namespace driphp\throws;


use driphp\KernelException;

class ConfigInvalidException extends KernelException
{

    public function getExceptionCode(): int
    {
        return 1005;
    }
}