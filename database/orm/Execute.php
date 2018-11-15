<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 15:01
 */

namespace driphp\database\builder;


abstract class Execute extends Builder
{
    /**
     * 执行查询,修改,插入操作
     * @return int 返回受影响行数(删除/修改)或插入数据的自增ID(插入)
     */
    abstract public function exec(): int;

}