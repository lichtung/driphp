<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 10:39
 */

namespace driphp\library\captcha;


use driphp\DripException;

class CaptchaException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1;
    }

}