<?php
/**
 * User: linzhv@qq.com
 * Date: 16/06/2018
 * Time: 12:25
 */
declare(strict_types=1);


namespace driphp\throws;


use driphp\DripException;

class ConfigException extends DripException
{
    public function getExceptionCode(): int
    {
        return 10200;
    }

}