<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 18:19
 */

namespace driphp\service\crawler;
use driphp\Component;

/**
 * Class UserAgentGenerator 浏览器标识生成器
 * @method UserAgentGenerator getInstance() static
 * @package driphp\service\crawler
 */
class UserAgentGenerator extends Component
{

    protected function initialize()
    {
    }

    public function random()
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063';
    }

}