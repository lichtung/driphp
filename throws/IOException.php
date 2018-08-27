<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 12:05
 */

namespace driphp\throws;


use driphp\DripException;

class IOException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1003;
    }
}