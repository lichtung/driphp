<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:32
 */
declare(strict_types=1);


namespace driphp\throws;


use driphp\DripException;

/**
 * Class ParameterInvalidException 非法参数
 * @package driphp\throws
 */
class ParameterInvalidException extends DripException
{

    public function getExceptionCode(): int
    {
        return 10700;
    }
}