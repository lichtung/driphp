<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 21:57
 */
declare(strict_types=1);


namespace driphp\throws;


use driphp\DriException;

/**
 * Class ClassNotFoundException 类不存在异常
 * @package driphp\throws\core
 */
class ClassNotFoundException extends DriException
{
    public function getExceptionCode(): int
    {
        return 10100;
    }
}