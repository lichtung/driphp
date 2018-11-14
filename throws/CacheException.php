<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 27/08/2018
 * Time: 12:07
 */

namespace driphp\throws;


use driphp\KernelException;

class CacheException extends KernelException
{

    public function getExceptionCode(): int
    {
        return 1012;
    }
}