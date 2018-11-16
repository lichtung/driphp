<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 16:59
 */

namespace driphp\database\orm;

use driphp\database\ORM;
use driphp\throws\database\NotFoundException;
use driphp\throws\database\QueryException;

/**
 * Class Query 查询生成器
 * @package driphp\database\orm
 */
class Query extends Builder
{

    public function __construct(ORM $context)
    {
        parent::__construct($context);
        $this->reset();
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
     * @param bool $reset
     * @return array
     * @throws \driphp\throws\database\GeneralException
     */
    public function build(bool $reset = true): array
    {
        if ($fields = $this->builder['fields']) {
            $_fields = '';
            foreach ($fields as $field) { # 测试
                if (stripos($field, ' as ') !== false) {
                    $_fields .= $field . ','; # 如 "count(1) as cc"
                } else {
                    $_fields .= $this->dao->escape($field) . ',';
                }
            }
            $fields = rtrim($_fields, ',');
        } else {
            $fields = ' * ';
        }
        $where = ' WHERE deleted_at IS NULL ';
        if (!empty($this->builder['where'])) {
            if (is_array($this->builder['where'])) {
                list($_where, $bind) = $this->parseWhere($this->builder['where']);
                $where .= ' AND ' . $_where;
            }
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
        $reset and $this->reset();
        return [$sql, $bind ?? []];
    }

    /**
     * 获取数量
     * @return int
     * @throws QueryException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     */
    public function count(): int
    {
        $this->builder['fields'] = ['count(1) as cc'];
        list($sql, $bind) = $this->build(false);
        $list = $this->dao->query($sql, $bind);
        if (empty($list)) {
            throw new QueryException('count empty');
        }
        return intval($list[0]['cc'] ?? 0);
    }

    /**
     * @return mixed
     * @throws NotFoundException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\QueryException
     */
    public function fetch()
    {
        $where = $this->builder['where'];
        $list = $this->fetchAll();
        if (empty($list)) {
            throw new NotFoundException($where);
        }
        return array_shift($list);
    }

    /**
     * @return array
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\QueryException
     */
    public function fetchAll()
    {
        list($sql, $bind) = $this->build();
        $list = $this->dao->query($sql, $bind);
        $result = [];
        $className = get_class($this->context);
        foreach ($list as $item) {
            /** @var ORM $orm */
            $orm = new $className($this->dao);
            $orm->setData($item);
            $result[$item['id']] = $orm;
        }
        return $result;

    }

    public function order(string... $fields)
    {
        if ($fields) {
            $buffer = '';
            foreach ($fields as $field) {
                $field = trim($field);
                $direct = '';
                if (strpos($field, ' ')) {
                    $field = explode(' ', $field);
                    $direct = $field[1];
                    $field = $field[0];
                }
                $buffer .= $this->dao->escape($field) . ' ' . $direct . ',';
            }
            $buffer = rtrim($buffer, ',');
            $this->builder['order'] = " ORDER BY {$buffer} ";
        }
        return $this;
    }

    public function group(string... $fields)
    {
        if ($fields) {
            $buffer = '';
            foreach ($fields as $field) {
                if (strpos($field, '.')) {
                    $array = explode('.', $field);
                    $buffer .= $array[0] . '.' . $this->dao->escape($array[1]) . ',';
                } else {
                    $buffer .= $this->dao->escape($field) . ',';
                }
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

}