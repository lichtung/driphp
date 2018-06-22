<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 22:28
 */
declare(strict_types=1);


namespace driphp\throws\service;


use driphp\DripException;

/**
 * Class EmailException 邮件异常
 * @package driphp\throws\service
 */
class EmailException extends DripException
{

    public function getExceptionCode(): int
    {
        return 20100;
    }
}