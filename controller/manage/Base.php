<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/29
 * Time: 20:15
 */

namespace controller\manage;

use driphp\Component;
use driphp\core\Request;
use driphp\core\response\View;
use driphp\service\manage\Sign;

/**
 * Class Base
 * @package controller\manage
 */
abstract class Base
{

    /**
     * 获取登录账户ID
     * @return int
     */
    protected function getUserId(): int
    {
        return Sign::factory()->getUserId();
    }

    protected function render()
    {
        $method = Component::getPrevious();
        return new View([
            'cdn' => Request::factory()->getPublicUrl(),
        ], $method);
    }
}