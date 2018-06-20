<?php
/**
 * User: linzhv@qq.com
 * Date: 13/04/2018
 * Time: 22:19
 */
declare(strict_types=1);


namespace driphp\throws\core;


use driphp\DripException;

/**
 * Class DriverNotDefinedException 驱动未定义
 * @package driphp\throws\core
 */
class DriverNotDefinedException extends DripException
{
    /**
     * DriverNotDefinedException constructor.
     * @param string $index
     */
    public function __construct(string $index)
    {
        parent::__construct("driver '{$index}' not found");
    }
}