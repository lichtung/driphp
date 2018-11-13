<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 9:56
 */

namespace controller\manage;


use driphp\core\Request;
use driphp\core\response\View;

class Index
{
    public function index()
    {
        return new View([
            'cdn' => Request::factory()->getPublicUrl(),
        ]);
    }
}