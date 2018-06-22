<?php
/**
 * User: linzhv@qq.com
 * Date: 05/05/2018
 * Time: 12:09
 */
declare(strict_types=1);


namespace driphp\throws\service;


use driphp\DripException;

/**
 * Class RabbitMQException RabbitMQ异常
 * @package driphp\throws\service
 */
class RabbitMQException extends DripException
{

    public function getExceptionCode(): int
    {
        return 20100;
    }

}