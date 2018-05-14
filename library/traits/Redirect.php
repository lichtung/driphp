<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 18:44
 */
declare(strict_types=1);


namespace sharin\library\traits;


use sharin\core\Response;
use sharin\FooBar;
use sharin\Kernel;

trait Redirect
{
    /**
     * 页面跳转
     * 与URI::redirect的区别是后者认为参数中的url是一个有效的跳转链接
     * @param string $compo 形式如'Cms/install/third' 的action定位
     * @param array $params URL参数
     * @param int $time 等待时间
     * @param string $message 跳转等待提示语
     * @return void
     */
    protected function redirect($compo, array $params = [], $time = 0, $message = '')
    {
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param string $title 跳转页面标题
     * @param bool $status 页面状态,true为积极的一面，false为消极的一面
     * @param string|bool $jumpBackOrUrl 页面操作，true时表示返回之前的页面，false时提示完毕后自动关闭窗口,如果是string则表示立即跳转
     * @param int $wait 页面等待时间
     * @return void
     */
    protected static function jump($message, $title = '跳转', $status = true, $jumpBackOrUrl = true, $wait = 1)
    {
        $vars = [];
        $vars['wait'] = $wait;
        $vars['title'] = $title;
        $vars['message'] = $message;
        $vars['status'] = $status ? 1 : 0;

        if (is_bool($jumpBackOrUrl)) {
            $vars['url'] = $jumpBackOrUrl ?
                'javascript:history.back(-1);' :
                'javascript:window.close();';
            Kernel::template('redirect', $vars);
        } else {
            $redirect = new \sharin\core\response\Redirect($jumpBackOrUrl);
            $redirect->nocache();
            exit($redirect);
        }
    }

    /**
     * 跳转到成功显示页面
     * @param string $message 提示信息
     * @param int $waitTime 等待时间
     * @param string $title 显示标题
     * @param bool|string $jumpBackOrUrl
     */
    protected function success($message, $waitTime = 1, $title = 'success', $jumpBackOrUrl = true)
    {
        static::jump($message, $title, true, $jumpBackOrUrl, $waitTime);
    }

    /**
     * 跳转到错误信息显示页面
     * @param string $message 提示信息
     * @param int $waitTime 等待时间
     * @param string $title 显示标题
     * @param bool|string $jumpBackOrUrl
     */
    protected function failure($message, $waitTime = 3, $title = 'error', $jumpBackOrUrl = true)
    {
        static::jump($message, $title, false, $jumpBackOrUrl, $waitTime);
    }
}