<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 10:18
 */

namespace driphp\model;


use driphp\database\ORM;

abstract class Model extends ORM
{

    public function tablePrefix(): string
    {
        return 'drip_';
    }
}