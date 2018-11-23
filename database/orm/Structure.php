<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 10:34
 */

namespace driphp\database\orm;


use driphp\throws\database\DefinitionException;

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
     * @throws DefinitionException
     */
    private function buildFields()
    {
        $structure = '';
        $indexKeys = [];
        $uniqueKeys = [];
        $foreignKeys = [];
        $tableStructure = $this->context->structure();
        if ($tableStructure) {
            # 添加默认字段
            foreach ($this->context->definedFields() as $k => $v) {
                isset($tableStructure[$k]) or $tableStructure[$k] = $v;
            }
        }

        foreach ($tableStructure as $name => $item) {
            $type = $item['type'] ?? 'varchar(255)';
            $notnull = empty($item['notnull']) ? '' : 'NOT NULL';
            $autoinc = empty($item['autoinc']) ? '' : 'AUTO_INCREMENT';
            $comment = empty($item['comment']) ? '' : "COMMENT '{$item['comment']}'";
            if ($type === 'timestamp') {
                $default = empty($item['default']) ? '' : "DEFAULT {$item['default']}";# timestamp格式 DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,无引号
            } else {
                $default = isset($item['default']) ? "DEFAULT '{$item['default']}'" : ''; # '' 则 default ''
            }
            $charset = empty($item['charset']) ? '' : "CHARACTER SET {$item['charset']}";

            $structure .= " `$name` $type $charset $notnull $default $autoinc $comment ,\n";
            empty($item['index']) or $indexKeys[] = $name;
            empty($item['unique']) or $uniqueKeys[] = $name;
            if (!empty($item['foreign'])) {
                $item['foreign']['_field'] = $name;
                $foreignKeys[] = $item['foreign'];
            }
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
        # foreign Key
        if ($foreignKeys) $structure .= $this->_buildForeignKeys($foreignKeys);
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

    /**
     * MySQL上restrict同no action @see https://stackoverflow.com/questions/5809954/mysql-restrict-and-no-action
     * @param array $foreignKeys
     * @return string
     * @throws DefinitionException
     */
    private function _buildForeignKeys(array $foreignKeys)
    {
        $structure = '';
        foreach ($foreignKeys as $foreignKey) {
            $table = $foreignKey['table'] ?? '';
            $field = $foreignKey['field'] ?? '';
            if (empty($table)) {
                throw new DefinitionException('foreign key require table name');
            }
            if (empty($field)) {
                throw new DefinitionException('foreign key require target field ');
            }
            $prefix = $foreignKey['prefix'] ?? $this->context->tablePrefix(); # 前缀默认去当前表的前缀
            $ondelete = $foreignKey['ondelete'] ?? 'CASCADE'; # 默认级联，在父表上update/delete记录时，同步update/delete掉子表的匹配记录
            $onupdate = $foreignKey['onupdate'] ?? 'RESTRICT'; # 如果子表中有匹配的记录,则不允许对父表对应候选键进行update/delete操作
            $key = md5(serialize($foreignKey));
            $structure .= "CONSTRAINT `{$key}` FOREIGN KEY (`{$foreignKey['_field']}`) REFERENCES `{$prefix}{$table}` (`{$field}`) ON DELETE {$ondelete} ON UPDATE {$onupdate},\n";
        }
        return $structure;
    }

}