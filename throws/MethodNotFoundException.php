<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:28
 */

namespace driphp\throws;


use driphp\DripException;

class MethodNotFoundException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1006;
    }

}