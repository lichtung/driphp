<?php
/**
 * User: linzhv@qq.com
 * Date: 24/04/2018
 * Time: 14:54
 */
declare(strict_types=1);


namespace driphp\throws\core\cache;


/**+
 * Class RedisException Redis异常
 * @package driphp\throws\core\cache
 */
class RedisException extends CacheException
{
    public function getExceptionCode(): int
    {
        return 2002;
    }
}