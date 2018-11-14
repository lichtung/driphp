<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 12:02
 */

namespace driphp\throws;


use driphp\KernelException;

class ClassNotFoundException extends KernelException
{

    public function getExceptionCode(): int
    {
        return 1001;
    }

}