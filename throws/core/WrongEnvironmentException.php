<?php
/**
 * User: linzhv@qq.com
 * Date: 13/04/2018
 * Time: 23:00
 */
declare(strict_types=1);


namespace driphp\throws\core;


use driphp\DripException;

/**
 * Class WrongEnvironmentException web模式下或者cli模式下不可用
 * @package driphp\throws\core
 */
class WrongEnvironmentException extends DripException
{

}