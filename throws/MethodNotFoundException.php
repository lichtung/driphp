<?php
/**
 * User: linzhv@qq.com
 * Date: 06/05/2018
 * Time: 14:40
 */
declare(strict_types=1);


namespace driphp\throws;


use driphp\DriException;

/**
 * Class MethodNotFoundException 方法不存在异常
 * @package driphp\throws
 */
class MethodNotFoundException extends DriException
{

    public function getExceptionCode(): int
    {
        return 10600;
    }
}