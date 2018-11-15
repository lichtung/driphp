<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 10:50
 */

namespace driphp\database\builder;


use driphp\throws\database\DataInvalidException;
use driphp\throws\database\ExecuteException;

/**
 * Class Update 模型更新
 * @package driphp\database\builder
 */
class Update extends Execute
{
    /**
     * @param bool $reset
     * @return array
     * @throws DataInvalidException
     */
    public function build(bool $reset = true): array
    {
        $fields = $this->builder['fields'] ?? [];
        $fields['updated_at'] = $this->getLocalDatetime();
        $wheres = $this->builder['wheres'] ?? [];
        if (empty($fields)) {
            throw new DataInvalidException('update fields should not be empty');
        }
        $_fields = '';
        $data = [];
        foreach ($fields as $field => $value) {
            $_fields .= $this->dao->escape($field) . ' = ? ,';
            $data[] = $value;
        }
        $fields = rtrim($_fields, ',');

        list($where, $bind) = $this->parseWhere($wheres);
        $bind = array_merge($data, $bind);

        return ["UPDATE `{$this->tableName}` SET {$fields} WHERE {$where} LIMIT 1;", $bind];
    }

    /**
     * @return int 返回0可能时前后数据没有修改
     * @throws DataInvalidException
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     */
    public function exec(): int
    {
        list($sql, $bind) = $this->build();
        return $this->dao->exec($sql, $bind);
    }

}