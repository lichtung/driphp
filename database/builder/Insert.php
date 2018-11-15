<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 10:41
 */

namespace driphp\database\builder;

/**
 * Class Insert 插入生成器
 * @package driphp\database\builder
 */
class Insert extends Builder
{

    public function build(): array
    {
        $data = $this->builder['fields'];
        # 默认赋值创建时间和修改时间
        $data['created_at'] = $data['updated_at'] = (new \DateTime('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s');

        $fields = array_keys($data);
        $binds = array_values($data);

        $_fields = '';
        foreach ($fields as $field) {
            $_fields .= $this->dao->escape($field) . ',';
        }
        $fields = rtrim($_fields, ',');
        $holder = rtrim(str_repeat('?,', count($binds)), ',');
        return ["INSERT INTO `{$this->tableName}` ( {$fields} ) VALUES ( {$holder} );", $binds];
    }

}