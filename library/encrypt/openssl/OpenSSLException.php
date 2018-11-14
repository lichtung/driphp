<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 16:02
 */

namespace driphp\library\encrypt\openssl;


use driphp\KernelException;

abstract class OpenSSLException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 100000;
    }
}