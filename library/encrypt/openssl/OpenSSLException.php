<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 16:02
 */

namespace driphp\library\encrypt\openssl;


use driphp\DripException;

abstract class OpenSSLException extends DripException
{
    public function getExceptionCode(): int
    {
        return 100000;
    }
}