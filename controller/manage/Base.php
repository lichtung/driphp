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
use driphp\core\response\JSON;
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

    /**
     * 渲染页面
     * @param array $data
     * @return View
     */
    protected function render(array $data = []): View
    {
        $method = Component::getPrevious();
        $data = array_merge([
            'cdn' => Request::factory()->getPublicUrl(),
        ], $data);
        return new View($data, $method);
    }

    /**
     * 返回正确的JSON响应
     * @param array $data
     * @param string $message
     * @return JSON
     */
    protected function success(array $data = [], string $message = ''): JSON
    {
        return new JSON([
            'code' => 0,
            'data' => $data,
            'message' => $message,
        ]);
    }

    protected function fail(string $message, int $code = -1, array $data = [])
    {
        return new JSON([
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ]);
    }
}