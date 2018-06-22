<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:42
 */
declare(strict_types=1);


namespace driphp\throws\core;


use driphp\DriException;

/**
 * Class DispatchException 调度异常
 * @package driphp\throws\core
 */
abstract class DispatchException extends DriException
{
    public function getExceptionCode(): int
    {
        return 20300;
    }
}