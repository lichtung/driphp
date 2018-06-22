<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/9 0009
 * Time: 18:23
 */

namespace driphp\throws\core\cache;

/**
 * Class RedisConnectException Redis连接异常
 * @package driphp\throws\core\cache
 */
class RedisConnectException extends RedisException
{
    public function getExceptionCode(): int
    {
        return 2001;
    }
}