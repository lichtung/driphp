<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 16:20
 */

namespace driphp\core\database\driver;

/**
 * Class OCI
 * @package driphp\core\database\driver
 * @deprecated
 */
class OCI extends Driver
{
    public function compile(array $components): string
    {
        // TODO: Implement compile() method.
    }

    public function escape(string $field): string
    {
        return "\"{$field}\"";
    }

    public function getFields(string $tableName): array
    {
        list($tableName) = explode(' ', $tableName);
        $result = $this->query("select a.column_name,data_type,decode(nullable,'Y',0,1) notnull,data_default,decode(a.column_name,b.column_name,1,0) pk "
            . "from user_tab_columns a,(select column_name from user_constraints c,user_cons_columns col "
            . "where c.constraint_name=col.constraint_name and c.constraint_type='P'and c.table_name='" . strtoupper($tableName)
            . "') b where table_name='" . strtoupper($tableName) . "' and a.column_name=b.column_name(+)");
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[strtolower($val['column_name'])] = array(
                    'name' => strtolower($val['column_name']),
                    'type' => strtolower($val['data_type']),
                    'notnull' => $val['notnull'],
                    'default' => $val['data_default'],
                    'primary' => $val['pk'],
                    'autoinc' => $val['pk'],
                );
            }
        }
        return $info;
    }

    public function getTables(string $tableName = ''): array
    {
        $result = $this->query("select table_name from user_tables");
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    public function buildDSN(array $config): string
    {
        $dsn = 'oci:dbname=//' . $config['hostname'] . ($config['hostport'] ? ':' . $config['hostport'] : '') . '/' . $config['database'];
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }

}