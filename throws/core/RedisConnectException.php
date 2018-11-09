<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 17:04
 */

namespace driphp\throws\core;


class RedisConnectException extends RedisException
{
    public function getExceptionCode(): int
    {
        return 10981;
    }
}