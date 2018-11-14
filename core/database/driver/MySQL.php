<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:20
 */
declare(strict_types=1);


namespace driphp\core\database\driver;

use PDO;
use driphp\Component;
use driphp\core\database\Dao;
use driphp\throws\database\ConnectException;

/**
 * Class MySQL
 * @package driphp\core\database\driver
 */
class MySQL extends Driver
{

    protected $config = [
        'name' => '',
        'user' => 'root',
        'passwd' => '123456',
        'host' => '127.0.0.1',
        'port' => 3306,
        'charset' => 'UTF8',
        'dsn' => null,
    ];

    public function escape(string $field): string
    {
        $field = (string)$field;
        return (strpos($field, '`') !== false) ? $field : "`{$field}`";
    }

    /**
     * MySQL constructor.
     * @param array $config
     * @param Component|Dao $context
     * @throws ConnectException
     */
    public function __construct(array $config, Component $context)
    {
        parent::__construct($config, $context);
        $this->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);# 如果设置为0，需要主动提交
    }


    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    public function buildDSN(array $config): string
    {
        $dsn = "mysql:host={$config['host']}";
        empty($config['name']) or $dsn .= ";dbname={$config['name']}";
        empty($config['port']) or $dsn .= ";port={$config['port']}";
        empty($config['socket']) or $dsn .= ";unix_socket={$config['socket']}";
        empty($config['charset']) or $dsn .= ";charset={$config['charset']}";//$this->options[\PDO::MYSQL_ATTR_INIT_COMMAND]    =   'SET NAMES '.$config['charset'];
        return $dsn;
    }


    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName 数据表名称
     * @return array
     */
    public function getFields(string $tableName): array
    {
        list($tableName) = explode(' ', $tableName);
        if (strpos($tableName, '.')) {
            list($dbName, $tableName) = explode('.', $tableName);
            $sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
        } else {
            $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        }

        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                if (\PDO::CASE_LOWER != $this->getAttribute(\PDO::ATTR_CASE)) {
                    $val = array_change_key_case($val, CASE_LOWER);
                }
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * @deprecated 可能没有权限
     * @param string $dbname
     * @return int
     */
    public function createSchema(string $dbname)
    {
        return $this->exec("CREATE SCHEMA `{$dbname}` DEFAULT CHARACTER SET utf8 ;");
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    public function getTables(string $dbName = ''): array
    {
        $sql = empty($dbName) ? 'SHOW TABLES;' : "SHOW TABLES FROM {$dbName};";
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * @inheritdoc
     */
    public function getDatabases(): array
    {
        $result = [];
        $list = $this->query('show databases;')->fetchAll(self::FETCH_ASSOC);
        foreach ($list as $item) {
            if ($name = $item['Database'] ?? '') {
                $result[] = $name;
            }
        }
        return $result;
    }

    /**
     * SELECT %DISTINCT% %FIELD% FROM %TABLE% %FORCE% %JOIN% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% %UNION% %LOCK% %COMMENT%;
     *
     * Avalable SQL:
     * SELECT DISTINCT
     *  a.aid,COUNT(is_show) as c
     * from
     *  blg_article a
     * INNER JOIN blg_article_pic ap on ap.aid = a.aid
     * INNER JOIN blg_article_tag bat on bat.aid = a.aid
     * WHERE a.author = 'bjy' and a.aid = 17
     * GROUP BY a.aid
     * HAVING COUNT(is_show) > 0
     * ORDER BY a.aid
     * LIMIT 0,1
     *
     * @param array $components
     * @return mixed
     */
    public function compile(array $components): string
    {
        //------------------------- join ------------------------------------------------//
        if (!empty($components['join']) and is_array($components['join'])) {
            $j = '';
            foreach ($components['join'] as $join) {
                $j .= "\n{$join}\n";
            }
            $components['join'] = $j;
        }

        //------------------------- limit ------------------------------------------------//
        if (isset($components['limit'])) {
            if (empty($components['offset'])) {
                $l = " LIMIT {$components['limit']} ";
            } else {
                $l = " LIMIT {$components['offset']},{$components['limit']} ";//                    $sql .= ' LIMIT '.$this->_options['offset'].' , '.$this->_options['limit'];
            }
            $components['limit'] = $l;
        }

        return str_replace([
            '%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%',
            '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'
        ], [
            $components['table'],
            !empty($components['distinct']) ? 'DISTINCT' : '',
            $components['fields'] ?: ' * ',
            $components['join'] ?: '',
            $components['where'] ?: '',
            $components['group'] ?: '',
            $components['having'] ?: '',
            $components['order'] ?: '',
            $components['limit'] ?: '',
            empty($components['union']) ? '' : $components['union'],
            empty($components['lock']) ? '' : $components['lock'],
            empty($components['comment']) ? '' : $components['comment'],
            empty($components['force']) ? '' : $components['force'],
        ], 'SELECT %DISTINCT% %FIELD% FROM %TABLE% %FORCE% %JOIN% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% %UNION% %LOCK% %COMMENT% ;');
    }

}