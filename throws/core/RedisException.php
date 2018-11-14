<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 17:08
 */

namespace driphp\throws\core;


use driphp\KernelException;

class RedisException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 10980;
    }
}