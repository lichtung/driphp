<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 16:59
 */

namespace driphp\core\database\builder;


use driphp\core\database\ORM;
use driphp\throws\database\QueryException;

class Query extends Builder
{

    public function __construct(ORM $context)
    {
        parent::__construct($context);
        $this->reset();
    }

    /**
     *      $fields ==> array(
     *          'fieldName' => 'fieldValue',
     *      );
     * format :
     * - INSERT INTO [table_name] VALUES  (value11, value12 ,....),(value21, value22 ,....)
     * - INSERT INTO table_name (column1, column2,...) VALUES (value11, value12 ,....),(value21, value22 ,....)
     * @return $this
     */
    public function reset()
    {
        $this->builder = [
            'distinct' => false,
            'table' => $this->tableName,//操作的数据表名称
            'alias' => '',
            'fields' => [],# 操作字段，查询时如果为空数组则等效于"*"
            /** @var array */
            'join' => null,
            'where' => null,//操作的where信息
            'group' => '',
            'order' => null,
            'having' => null,
            'limit' => 10,
            'offset' => 0,
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
        $this->builder['distinct'] = $distinct;
        return $this;
    }

    /**
     * @param array $fields
     * @throws QueryException
     */
    public function fields(array $fields)
    {
        $structure = $this->context->structure();
        foreach ($fields as $index => $item) {
            if (!isset($structure[$index])) throw new QueryException("fields '$index' not found in {$this->tableName}");
        }
    }

    /**
     * 数据表别名
     * @param string $alias
     * @return $this
     */
    public function alias(string $alias)
    {
        $this->builder['alias'] = $alias;
        return $this;
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
            $tableName = str_replace(['{{', '}}'], [$this->context->tablePrefix(), ''], $tableName);
        }
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
        if (isset($this->builder['join'])) {
            $this->builder['join'][] = $join;
        } else {
            $this->builder['join'] = [$join];
        }
        return $this;
    }

    /**
     * @param string $join statement without 'INNER JOIN'
     * @return $this
     */
    public function innerJoin($join)
    {
        return $this->join($join, 'INNER');
    }

    /**
     * @param string $join statement without 'LEFT OUTER JOIN'
     * @return $this
     */
    public function leftJoin($join)
    {
        return $this->join($join, 'LEFT OUTER');
    }

    /**
     * 设置where条件
     * @param array $where
     * @return $this
     */
    public function where(array $where)
    {
        $this->builder['where'] = $where;
        return $this;
    }

    public function build(): array
    {
        if ($fields = $this->builder['fields']) {
            $_fields = '';
            foreach ($fields as $field => $value) {
                $_fields .= $this->dao->escape($field) . ',';
            }
            $fields = rtrim($_fields, ',');
        } else {
            $fields = ' * ';
        }
        if ($where = $this->builder['where'] ?? false) {
            if (is_array($this->builder['where'])) {
                list($where, $bind,) = $this->_parseWhere($this->builder['where']);
            }
            $where = "WHERE {$where}";
        } else { # where 子句不能为空
            $where = '';
        }
        $tableName = $this->builder['table'] ?: $this->tableName;
        if ($this->builder['alias']) {
            $tableName .= ' as ' . $this->builder['alias'];
        }
        $sql = $this->dao->compile([
            # 查询
            'distinct' => $this->builder['distinct'],
            'table' => $tableName,//操作的数据表名称
            'fields' => $fields,# 操作字段，查询时如果为空数组则等效于"*"
            /** @var array */
            'join' => $this->builder['join'],
            'where' => $where,//操作的where信息
            'group' => $this->builder['group'],
            'order' => $this->builder['order'],
            'having' => $this->builder['having'],
            'limit' => $this->builder['limit'],
            'offset' => $this->builder['offset'],
        ]);
        return [$sql, $bind ?? []];
    }


    public function whereOr()
    {
    }

    public function whereAnd()
    {
    }

    public function whereIn()
    {
    }

    public function whereNotIn()
    {
    }

    public function whereBetween()
    {
    }

    public function orderBy(string... $fields)
    {
        if ($fields) {
            $buffer = '';
            foreach ($fields as $field) {
                $field = trim($field);
                if (strpos($field, ' ')) {
                    $field = explode(' ', $field);
                    $buffer .= $this->dao->escape($field[0]) . ' ' . $field[1] . ',';
                } else {
                    $buffer .= $this->dao->escape($field) . ',';
                }
            }
            $buffer = rtrim($buffer, ',');
            $this->builder['order'] = " ORDER BY {$buffer} ";
        }
        return $this;
    }

    public function groupBy(string... $fields)
    {
        if ($fields) {
            $buffer = '';
            foreach ($fields as $field) {
                $buffer .= $this->dao->escape($field) . ',';
            }
            $buffer = rtrim($buffer, ',');
            $this->builder['group'] = " GROUP BY {$buffer} ";
        }
        return $this;
    }

    public function limit(int $limit)
    {
        $this->builder['limit'] = $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->builder['offset'] = $offset;
        return $this;
    }

    /**
     * 在 SQL 中增加 HAVING 子句原因是，WHERE 关键字无法与合计函数一起使用。
     * @param string $having
     * @return $this
     */
    public function having(string $having)
    {
        $this->builder['having'] = "HAVING {$having}";
        return $this;
    }

    /**
     * 解析where
     * @param array $where
     * @return array
     */
    private function _parseWhere(array $where): array
    {
        $_where = ' 1 ';
        $bind = $raw = [];
        foreach ($where as $index => $item) {
            $_where .= "AND `$index` = ? ";
            $raw[$item] = $bind[] = $item;
        }
        return [$_where, $bind, $raw];
    }
}