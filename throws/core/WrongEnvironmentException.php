<?php
/**
 * User: linzhv@qq.com
 * Date: 13/04/2018
 * Time: 23:00
 */
declare(strict_types=1);


namespace sharin\throws\core;


use sharin\SharinException;

/**
 * Class WrongEnvironmentException web模式下或者cli模式下不可用
 * @package sharin\throws\core
 */
class WrongEnvironmentException extends SharinException
{

}