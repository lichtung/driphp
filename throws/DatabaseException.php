<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 24/08/2018
 * Time: 11:21
 */

namespace driphp\throws;


use driphp\KernelException;

class DatabaseException extends KernelException
{
    public function __construct($message, int $code = -1)
    {
        parent::__construct($message, $code);
    }

    public function getExceptionCode(): int
    {
        return 1009;
    }
}