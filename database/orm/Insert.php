<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 10:41
 */

namespace driphp\database\builder;

use driphp\throws\database\ExecuteException;

/**
 * Class Insert 插入生成器
 * @package driphp\database\builder
 */
class Insert extends Execute
{

    public function build(bool $reset = true): array
    {
        $data = $this->builder['fields'];
        # 默认赋值创建时间和修改时间
        $data['created_at'] = $data['updated_at'] = $this->getLocalDatetime();

        $fields = array_keys($data);
        $binds = array_values($data);

        $_fields = '';
        foreach ($fields as $field) {
            $_fields .= $this->dao->escape($field) . ',';
        }
        $fields = rtrim($_fields, ',');
        $holder = rtrim(str_repeat('?,', count($binds)), ',');
        $reset and $this->reset();
        return ["INSERT INTO `{$this->tableName}` ( {$fields} ) VALUES ( {$holder} );", $binds];
    }


    /**
     * 执行插入操作
     * @return int 返回插入数据的自增ID
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     */
    public function exec(): int
    {
        list($sql, $bind) = $this->build();
        if (1 !== $this->dao->exec($sql, $bind)) {
            throw new ExecuteException('insert failed');
        }
        return $this->dao->lastInsertId();
    }

}