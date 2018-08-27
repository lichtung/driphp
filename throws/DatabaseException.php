<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 24/08/2018
 * Time: 11:21
 */

namespace driphp\throws;


use driphp\DripException;

class DatabaseException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1009;
    }
}