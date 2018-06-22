<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 12:34
 */
declare(strict_types=1);


namespace driphp\throws;


use driphp\DriException;

/**
 * Class IOException IO异常
 * @package driphp\throws
 */
class IOException extends DriException
{

    public function getExceptionCode(): int
    {
        return 10500;
    }
}