<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 10:35
 */
declare(strict_types=1);


namespace driphp\throws\core\logger;


use driphp\DripException;

/**
 * Class BadLoggerLevelException 错误的日志记录级别
 * @package driphp\throws\core
 */
class BadLoggerLevelException extends DripException
{
    public function getExceptionCode(): int
    {
        return 3201;
    }
}