<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 9:56
 */

namespace controller\manage;


use driphp\Component;
use driphp\core\Request;
use driphp\core\response\View;

class Index
{
    public function index()
    {
        return $this->render();
    }

    protected function render()
    {
        $method = Component::getPrevious();
        return new View([
            'cdn' => Request::factory()->getPublicUrl(),
        ], $method);
    }
}