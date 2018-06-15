<?php
/**
 * User: linzhv@qq.com
 * Date: 21/03/2018
 * Time: 09:07
 */
declare(strict_types=1);


namespace driphp\core\database;

use driphp\Kernel;
use driphp\throws\core\database\ExecuteException;
use driphp\throws\core\database\QueryException;
use driphp\throws\ParameterInvalidException;

/**
 * Class Model
 *
 * @method bool beginTransaction()
 * @method bool commit() commit current transaction
 * @method bool rollback() rollback current transaction
 * @method bool inTransaction()  check if is in a transaction
 * @method int lastInsertId($name = null) get auto-inc id of last insert record
 *
 * @method string escape($field)
 * @method string compile(array $components)
 *
 * @method array|string getLastSql(bool $all = false) 获取上一次查询的sql语句
 * @method array getLastParams(bool $all = false) 获取上一次查询的输入参数
 *
 * @package dripex\database
 */
abstract class Model
{
    const ENGINE_INNODB = 'InnoDB';
    const ENGINE_MYISAM = 'MyISAM';
    /**
     * @var Dao
     */
    private $_dao = null;

    /**
     * @var string|array  数组表示符合主键
     */
    protected $primaryKey = 'id';
    /**
     * @var string 表前缀
     */
    protected $tablePrefix = '';
    /**
     * @var string 表名称（不含前缀）
     */
    protected $tableName = '';
    /**
     * @var array 唯一主键列表
     */
    protected $uniqueKeys = [];
    /**
     * @var array 字段列表
     */
    protected $tableFields = [];
    /**
     * @var string 存储引擎
     */
    protected $tableStorageEngine = self::ENGINE_INNODB;
    /**
     * @var string 表前缀+表名称
     */
    protected $_table = '';

    public function __construct(Dao $dao = null)
    {
        $this->_dao = $dao;
        $this->_table = $this->tablePrefix . $this->tableName;
        $this->reset();
    }

    /**
     * @param Dao|null $dao
     * @return mixed
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     */
    public static function getInstance(Dao $dao = null)
    {
        static $_instances = [];
        $key = $dao->drive()->getDSN();
        if (!isset($_instances[$key])) {
            try {
                /** @var Model $object */
                $_instances[$key] = Kernel::factory(static::class, [$dao]);
            } catch (\Throwable $throwable) {
            }
        }
        return $_instances[$key];
    }

    /**
     * @return Dao
     */
    public function dao(): Dao
    {
        if (null === $this->_dao) $this->_dao = Dao::getInstance();
        return $this->_dao;
    }

    /**
     * @return bool
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\QueryException
     */
    public function installed(): bool
    {
        $sql = "SHOW TABLES LIKE '{$this->_table}' ;";
        return count($this->dao()->query($sql)) === 1;
    }

    /**
     * @return void
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     */
    public function uninstall(): void
    {
        $sql = "DROP TABLE IF EXISTS `{$this->_table}`; ";
        $this->dao()->exec($sql);
    }

    /**
     * @return void
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     */
    final public function install(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->_table}` 
              ( {$this->_buildStructure()} ) 
              ENGINE={$this->tableStorageEngine} DEFAULT CHARSET=utf8;";
        $this->dao()->exec($sql);
    }

    private function _buildStructure()
    {
        $structure = '';
        $indexKeys = [];
        $uniqueKeys = $this->uniqueKeys;
        foreach ($this->tableFields as $name => $item) {
            $type = $item['type'] ?? 'varchar(255)';
            $notnull = empty($item['notnull']) ? '' : 'NOT NULL';
            $autoinc = empty($item['autoinc']) ? '' : 'AUTO_INCREMENT';
            $comment = empty($item['comment']) ? '' : "COMMENT '{$item['comment']}'";
            if ($type === 'timestamp') {
                $default = empty($item['default']) ? '' : "DEFAULT {$item['default']}";# timestamp格式 CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            } else {
                $default = empty($item['default']) ? '' : "DEFAULT '{$item['default']}'";
            }
            $charset = empty($item['charset']) ? '' : "CHARACTER SET {$item['charset']}";

            $structure .= " `$name` $type $charset $notnull $default $autoinc $comment ,";
            empty($item['index']) or $indexKeys[] = $name;
            empty($item['unique']) or $uniqueKeys[] = $name;
        }
        # Primary Key
        if (is_array($this->primaryKey)) {
            $pk = implode('`,`', $this->primaryKey);
        } else {
            $pk = $this->primaryKey;
        }
        $structure .= "PRIMARY KEY (`{$pk}`),";
        # Index Key
        if ($indexKeys) $structure .= $this->_buildKeys($indexKeys, 'KEY');
        # Unique Key
        if ($uniqueKeys) $structure .= $this->_buildKeys($uniqueKeys, 'UNIQUE KEY');
        return rtrim($structure, ',');
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
            $id = sha1($this->_table . $item . $type);
            if (isset($flags[$id])) continue;
            $structure .= "$type `$id` (`$item`),";
            $flags[$id] = true;
        }
        return $structure;
    }

    /**
     * @var array SQL的各个组成部分
     */
    protected $_combination = [];
    /**
     * @var array SQL的输入参数
     */
    protected $_combinationBind = [];

    ############################################# 链式操作 #########################################################

    /**
     * @return $this
     */
    public function reset()
    {
        $this->_combination = [
            //查询
            'distinct' => false,
            'table' => null,//操作的数据表名称
            'alias' => '',
            /**
             *      $fields ==> array(
             *          'fieldName' => 'fieldValue',
             *      );
             * format :
             * - INSERT INTO [table_name] VALUES  (value11, value12 ,....),(value21, value22 ,....)
             * - INSERT INTO table_name (column1, column2,...) VALUES (value11, value12 ,....),(value21, value22 ,....)
             *
             */
            'fields' => [],# 操作字段，查询时如果为空数组则等效于"*"
            /** @var array */
            'join' => null,
            'where' => null,//操作的where信息
            'group' => null,
            'order' => null,
            'having' => null,
            'limit' => null,
            'offset' => null,
        ];
        return $this;
    }

    /**
     * 是否唯一
     * @param bool $distinct
     * @return $this
     */
    public function distinct(bool $distinct = true)
    {
        $this->_combination['distinct'] = $distinct;
        return $this;
    }

    /**
     * 设置查询字段或者修改字段和值
     * @param array|string $fields string类型时表示查询，array时表示修改
     * @return $this
     * @throws ParameterInvalidException 字段不存在或者参数不正确时抛出
     */
    public function fields($fields): Model
    {
        $_fields = [];
        if (is_array($fields)) {
            foreach ($fields as $index => $item) {
                if (!isset($this->tableFields[$index])) throw new ParameterInvalidException("fields '$index' not found in {$this->_table}");
            }
        } elseif (is_string($fields)) {
            $temp = explode(',', $fields);
            foreach ($temp as $index) {
                if (!isset($this->tableFields[$index])) throw new ParameterInvalidException("fields '$index' not found in {$this->_table}");
                $_fields[$index] = true;
            }
        } else {
            throw new ParameterInvalidException($fields);
        }
        $this->_combination['fields'] = $_fields;
        return $this;
    }

    /**
     * 设置当前要操作的数据表
     * @param string $tableName
     * @return $this
     */
    public function table(string $tableName)
    {
        $this->_replaceTablePrefix($tableName);
        $this->_combination['table'] = $tableName;
        return $this;
    }

    /**
     * set alias for current table
     * @param string $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->_combination['alias'] = $alias;
        return $this;
    }

    /**
     * 只针对mysql有效
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0)
    {
        $this->_combination['limit'] = $limit;
        $this->_combination['offset'] = $offset;
        return $this;
    }

    /**
     * @param string $group
     * @return $this
     */
    public function group(string $group)
    {
        # TODO: _replaceTablePrefix
        $this->_combination['group'] = "GROUP BY {$group} ";
        return $this;
    }

    /**
     * 设置当前要操作的数据的排列顺序
     * @param string $order
     * @return $this
     */
    public function order(string $order)
    {
        # TODO: _replaceTablePrefix
        $this->_combination['order'] = "ORDER BY {$order}";
        return $this;
    }

    /**
     * 在 SQL 中增加 HAVING 子句原因是，WHERE 关键字无法与合计函数一起使用。
     * @param string $having
     * @return Model
     */
    public function having($having)
    {
        $this->_combination['having'] = "HAVING {$having}";
        return $this;
    }

    /**
     * @param string $join
     * @param string $type ''表示"JOIN" ,'inner' 表示INNER JOIN ，'left'表示LEFT (OUTER) JOIN
     * @return $this
     */
    public function join(string $join, string $type = '')
    {
        $this->_replaceTablePrefix($join);
        $join = " {$type} JOIN {$join} ";
        if (isset($this->_combination['join'])) {
            $this->_combination['join'][] = $join;
        } else {
            $this->_combination['join'] = [$join];
        }
        return $this;
    }

    /**
     * 设置where条件
     * @param array $where
     * @return $this
     */
    public function where(array $where)
    {
        $this->_combination['where'] = $where;
        return $this;
    }

    /**
     * @param string $join statement without 'INNER JOIN'
     * @return Model
     */
    public function innerJoin($join)
    {
        return $this->join($join, 'INNER');
    }

    /**
     * @param string $join statement without 'LEFT OUTER JOIN'
     * @return Model
     */
    public function leftJoin($join)
    {
        return $this->join($join, 'LEFT OUTER');
    }

    /**
     * YII style
     * replace tablename placeholder with tablename with prefix
     * It used for tablename format and join format
     *
     * where the table stand is "FROM" and "JOIN"
     *
     *  <code>
     *      //code below performs low
     *      preg_replace('/\{\{([\d\w_]+)\}\}/',"{$this->tablePrefix()}$1",$tableName);
     *  </code>
     * @param string $tableName table name without prefix
     * @return void
     */
    private function _replaceTablePrefix(&$tableName)
    {
        if (strpos($tableName, '{{') !== false) {
            $tableName = str_replace(['{{', '}}'], [$this->tablePrefix, ''], $tableName);
        }
    }

    protected function _parseSetFields(array $fields)
    {


    }

    /**
     * 解析where
     * @param array $where
     * @return array
     */
    protected function _parseWhere(array $where): array
    {
        $_where = ' 1 ';
        $bind = $raw = [];
        foreach ($where as $index => $item) {
            $_where .= "AND `$index` = ? ";
            $raw[$item] = $bind[] = $item;
        }
        return [$_where, $bind, $raw];
    }
    ########################################## CURD #########################################################

    /**
     * @return bool
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     */
    public function insert(): bool
    {
        if ($data = $this->_combination['fields']) {
            $dao = $this->dao();

            $fields = array_keys($data);
            $values = array_values($data);

            $_fields = '';
            foreach ($fields as $field) {
                $_fields .= $dao->escape($field) . ',';
            }
            $fields = rtrim($_fields, ',');
            $holder = rtrim(str_repeat('?,', count($values)), ',');

            return $dao->exec("INSERT INTO `{$this->_table}` ( {$fields} ) VALUES ( {$holder} );", $values) === 1;
        } else {
            throw new ExecuteException("No data to insert");
        }
    }

    /**
     * @return bool
     * @throws QueryException
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     */
    public function delete(): bool
    {
        if ($where = $this->_combination['where'] ?? false) {
            if (is_array($this->_combination['where'])) {
                list($where, $bind,) = $this->_parseWhere($this->_combination['where']);
            } else {  # is_string
                $bind = null;
            }
            $sql = "DELETE FROM {$this->_table} WHERE {$where} LIMIT 1;";
            return $this->dao()->exec($sql, $bind) === 1;
        } else { # where 子句不能为空
            throw new QueryException('No where statement');
        }
    }

    public function getCombinationBind()
    {
        return $this->_combinationBind;
    }

    /**
     * @return string
     */
    public function buildSQL(): string
    {
        $dao = $this->dao();
        if ($fields = $this->_combination['fields']) {
            $_fields = '';
            foreach ($fields as $field => $value) {
                $_fields .= $dao->escape($field) . ',';
            }
            $fields = rtrim($_fields, ',');
        } else {
            $fields = ' * ';
        }
        if ($where = $this->_combination['where'] ?? false) {
            if (is_array($this->_combination['where'])) {
                list($where, $bind,) = $this->_parseWhere($this->_combination['where']);
                $this->_combinationBind = $this->_combinationBind ? array_merge($this->_combinationBind, $bind) : $bind;
            }
            $where = "WHERE {$where}";
        } else { # where 子句不能为空
            $where = '';
        }
        return $dao->compile([
            # 查询
            'distinct' => $this->_combination['distinct'],
            'table' => ($this->_combination['table'] ?: $this->_table) . ' ' . $this->_combination['alias'],//操作的数据表名称
            'fields' => $fields,# 操作字段，查询时如果为空数组则等效于"*"
            /** @var array */
            'join' => $this->_combination['join'],
            'where' => $where,//操作的where信息
            'group' => $this->_combination['group'],
            'order' => $this->_combination['order'],
            'having' => $this->_combination['having'],
            'limit' => $this->_combination['limit'],
            'offset' => $this->_combination['offset'],
        ]);
    }
}