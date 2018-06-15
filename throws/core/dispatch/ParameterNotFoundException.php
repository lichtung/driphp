<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:41
 */
declare(strict_types=1);


namespace driphp\throws\core\dispatch;

use driphp\throws\core\DispatchException;

/**
 * Class ParameterNotFoundException 操作参数不存在异常
 * @package driphp\throws\core\dispatch
 */
class ParameterNotFoundException extends DispatchException
{
    public function __construct(string $message, int $code = -1)
    {
        parent::__construct("action parameter '$message' not found");
    }

}