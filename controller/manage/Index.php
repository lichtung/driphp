<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 9:56
 */

namespace controller\manage;

use driphp\core\response\Redirect;

/**
 * Class Index 首页控制器
 * @package controller\manage
 */
class Index extends Base
{
    /** @var int */
    protected $uid;

    public function __construct()
    {
        $this->uid = $this->getUserId();
        if (!$this->uid) {
            die(new Redirect('/manage/sign/in'));
        }
    }

    /**
     * 首页
     * @return \driphp\core\response\View
     */
    public function index()
    {
        return $this->render();
    }

    /**
     * 获取服务状态
     *
     * 查看系统版本号 [cat /etc/redhat-release] => “CentOS Linux release 7.5.1804 (Core)”
     * 查看主机名    [cat /etc/hostname] => 'centos74'
     * @return array
     */
    protected function status()
    {
        return [];
    }

}