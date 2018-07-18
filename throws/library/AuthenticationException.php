<?php
/**
 * User: linzhv@qq.com
 * Date: 23/06/2018
 * Time: 15:01
 */
declare(strict_types=1);


namespace driphp\throws\library;


use driphp\DripException;

class AuthenticationException extends DripException
{
    public function getExceptionCode(): int
    {
        return 1;
    }
}