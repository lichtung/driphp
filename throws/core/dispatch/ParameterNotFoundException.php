<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:41
 */
declare(strict_types=1);


namespace sharin\throws\core\dispatch;

use sharin\throws\core\DispatchException;

class ParameterNotFoundException extends DispatchException
{
    public function __construct(string $message, int $code = -1)
    {
        parent::__construct("action parameter '$message' not found");
    }

}