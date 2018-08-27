<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 23/08/2018
 * Time: 10:15
 */

namespace driphp\throws\core;


use driphp\throws\CoreException;

class ActionNotFoundException extends CoreException
{
    public function getExceptionCode(): int
    {
        return 10082;
    }

}