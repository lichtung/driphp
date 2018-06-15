<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 16:11
 */
declare(strict_types=1);


namespace driphp\core;


use driphp\Component;

/**
 * Class Midware 中间件
 *
 * 任何请求在执行前都需要经过中间件（包括执行调度的地方）
 *
 * @package driphp\core
 */
class Midware extends Component
{
    protected function initialize()
    {
    }
}