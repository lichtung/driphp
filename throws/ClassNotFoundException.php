<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 12:02
 */

namespace driphp\throws;


use driphp\DripException;

class ClassNotFoundException extends DripException
{

    public function getExceptionCode(): int
    {
        return 1001;
    }

}