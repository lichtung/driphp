<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:35
 */
declare(strict_types=1);


namespace driphp\throws\core\dispatch;

use driphp\throws\core\DispatchException;

/**
 * Class ModulesNotFoundException 模块不存在异常
 * @package driphp\throws\core\dispatch
 */
class ModulesNotFoundException extends DispatchException
{

    public function getExceptionCode(): int
    {
        return 3104;
    }
}