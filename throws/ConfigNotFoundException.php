<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 12:03
 */

namespace driphp\throws;


use driphp\DripException;

class ConfigNotFoundException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1002;
    }

}