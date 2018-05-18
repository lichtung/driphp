<?php
/**
 * User: linzhv@qq.com
 * Date: 13/04/2018
 * Time: 22:19
 */
declare(strict_types=1);


namespace sharin\throws\core;


use sharin\SharinException;

/**
 * Class DriverNotDefinedException 驱动未定义
 * @package sharin\throws\core
 */
class DriverNotDefinedException extends SharinException
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