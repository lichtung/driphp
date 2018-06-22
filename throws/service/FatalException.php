<?php
/**
 * User: linzhv@qq.com
 * Date: 05/05/2018
 * Time: 14:47
 */
declare(strict_types=1);


namespace driphp\throws\service;


use driphp\DripException;

/**
 * Class FatalException
 * @package driphp\throws\service
 */
class FatalException extends DripException
{

    public function getExceptionCode(): int
    {
        return 20100;
    }

}