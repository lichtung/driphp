<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:16
 */

namespace driphp\throws;


use driphp\DripException;

class ConfigInvalidException extends DripException
{

    public function getExceptionCode(): int
    {
        return 1005;
    }
}