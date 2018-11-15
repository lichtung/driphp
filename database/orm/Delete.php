<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 10:50
 */

namespace driphp\database\orm;


class Delete extends Execute
{
    public function build(bool $reset = true): array
    {
        list($where, $bind) = $this->parseWhere($this->builder['where']);
        $sql = "DELETE FROM {$this->tableName} WHERE {$where} LIMIT 1;";
        return [$sql, $bind];
    }

}