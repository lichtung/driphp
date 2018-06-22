<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 22:06
 */
declare(strict_types=1);


namespace driphp\throws\core\logger;


use driphp\DripException;

/**.
 * Class MessageEmptyException 日志消息未空异常
 * @package driphp\throws\core\logger
 */
class MessageEmptyException extends DripException
{

    public function getExceptionCode(): int
    {
        return 3200;
    }
}