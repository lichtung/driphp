<?php
/**
 * User: linzhv@qq.com
 * Date: 16/06/2018
 * Time: 12:25
 */
declare(strict_types=1);


namespace driphp\throws;


use driphp\DriException;

class ConfigException extends DriException
{
    public function getExceptionCode(): int
    {
        return 10200;
    }

}