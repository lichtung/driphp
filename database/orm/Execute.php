<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 15:01
 */

namespace driphp\database\orm;


abstract class Execute extends Builder
{
    /**
     * 执行查询,修改,插入操作
     * @return int 返回受影响行数(删除/修改)或插入数据的自增ID(插入)
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\ExecuteException
     */
    public function exec(): int
    {
        list($sql, $bind) = $this->build();
        return $this->dao->exec($sql, $bind);
    }
}