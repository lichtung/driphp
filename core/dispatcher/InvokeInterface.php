<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 19:07
 */
declare(strict_types=1);


namespace driphp\core\dispatcher;

use driphp\core\Request;

/**
 * Interface InvokeInterface
 * @deprecated
 * @package driphp\core\dispatcher
 */
interface InvokeInterface
{
    /**
     * 执行
     * @param Request $request
     * @return bool
     */
    public function invoke(Request $request): bool;
}