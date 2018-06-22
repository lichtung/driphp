<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 15:52
 */
declare(strict_types=1);


namespace driphp\throws\core\cache;


use driphp\DriException;

/**
 * Class CacheException 缓存异常
 * @package driphp\throws\core\cache
 */
class CacheException extends DriException
{
    public function getExceptionCode(): int
    {
        return 20100;
    }
}