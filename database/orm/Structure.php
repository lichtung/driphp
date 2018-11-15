<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 10:34
 */

namespace driphp\database\builder;


class Structure extends Builder
{
    public function build(bool $reset = true): array
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` ( 
{$this->buildFields()} 
) ENGINE={$this->context->tableStorageEngine()} DEFAULT CHARSET=utf8;";
        return [$sql, []];
    }

    /**
     * 创建结构
     * @return string
     */
    private function buildFields()
    {
        $structure = '';
        $indexKeys = [];
        $uniqueKeys = [];
        $tableStructure = $this->context->structure();
        if ($tableStructure) {
            isset($tableStructure['id']) or $tableStructure['id'] = [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'autoinc' => true,
                'comment' => '主键',
            ];

            isset($tableStructure['created_at']) or $tableStructure['created_at'] = [
                'type' => 'datetime',
                'notnull' => true,
                'comment' => '记录添加时间',
            ];
            isset($tableStructure['updated_at']) or $tableStructure['updated_at'] = [
                'type' => 'datetime',
                'notnull' => true,
                'comment' => '记录修改时间',
            ];
            isset($tableStructure['deleted_at']) or $tableStructure['deleted_at'] = [
                'type' => 'datetime',
                'notnull' => false,
                'comment' => '记录软删除时间,为null时候表示已经删除',
                'default' => null,
            ];
        }

        foreach ($tableStructure as $name => $item) {
            $type = $item['type'] ?? 'varchar(255)';
            $notnull = empty($item['notnull']) ? '' : 'NOT NULL';
            $autoinc = empty($item['autoinc']) ? '' : 'AUTO_INCREMENT';
            $comment = empty($item['comment']) ? '' : "COMMENT '{$item['comment']}'";
            if ($type === 'timestamp') {
                $default = empty($item['default']) ? '' : "DEFAULT {$item['default']}";# timestamp格式 DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,无引号
            } else {
                $default = empty($item['default']) ? '' : "DEFAULT '{$item['default']}'";
            }
            $charset = empty($item['charset']) ? '' : "CHARACTER SET {$item['charset']}";

            $structure .= " `$name` $type $charset $notnull $default $autoinc $comment ,\n";
            empty($item['index']) or $indexKeys[] = $name;
            empty($item['unique']) or $uniqueKeys[] = $name;
        }
        # Primary Key
        if ($primaryKeys = $this->context->primaryKeys()) {
            $pk = implode('`,`', $primaryKeys);
        } else {
            $pk = $this->context->primaryKey();
        }
        $structure .= " PRIMARY KEY (`{$pk}`),\n";
        # 如果即是Index,又是UniqueIndex, 保留UniqueIndex
        foreach ($indexKeys as $index => $indexKey) {
            if (in_array($indexKey, $uniqueKeys)) {
                unset($indexKeys[$index]);
            }
        }
        # Index Key
        if ($indexKeys) $structure .= $this->_buildKeys($indexKeys, 'KEY');
        # Unique Key
        if ($uniqueKeys) $structure .= $this->_buildKeys($uniqueKeys, 'UNIQUE KEY');
        return rtrim($structure, ",\n");
    }

    /**
     * @param array $keys
     * @param string $type Index - 'KEY', UNIQUE - 'UNIQUE KEY'
     * @return string
     */
    private function _buildKeys(array $keys, string $type = 'KEY'): string
    {
        $flags = []; # 避免同类键重复
        $structure = '';
        foreach ($keys as $item) {
            if (is_array($item)) {
                $item = implode('`,`', $item);
            }
            $id = sha1($this->tableName . $item . $type);
            if (isset($flags[$id])) continue;
            $structure .= " $type `$id` (`$item`),\n";
            $flags[$id] = true;
        }
        return $structure;
    }

}