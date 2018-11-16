<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 16:59
 */

namespace driphp\database\orm;

use driphp\database\Dao;
use driphp\database\ORM;
use driphp\throws\database\GeneralException;
use driphp\throws\database\QueryException;

/**
 * Class Builder 生成器
 * @package driphp\database\orm
 */
abstract class Builder
{
    /** @var ORM */
    protected $context;
    /** @var string */
    protected $tableName = '';
    /** @var array */
    protected $builder = [];
    /** @var array */
    protected $binder = [];
    /** @var Dao */
    protected $dao;

    public function __construct(ORM $context)
    {
        $this->context = $context;
        $this->dao = $context->dao();
        $this->tableName = $this->context->tablePrefix() . $this->context->tableName();
        $this->reset();
    }


    /**
     * @param array $fields
     * @return $this
     * @throws QueryException
     */
    public function fields(array $fields)
    {
        $structure = $this->context->structure();
        foreach ($fields as $index => $_) {
            if (is_numeric($index)) {
                $index = $_; # ->fields(['username', 'email']) 查询是是这样的结构(只有值没有键，用于筛选查询字段)
            }
            if (in_array($index, ['created_at', 'updated_at', 'deleted_at'])) continue; # 这三个字段无法修改
            if (!isset($structure[$index])) throw new QueryException("fields '$index' not found in {$this->tableName}");
        }
        $this->builder['fields'] = $fields;
        return $this;
    }

    /**
     * 设置where条件
     * @param array $where
     * @return $this
     */
    public function where(array $where)
    {
        $this->builder['where'] = array_merge($this->builder['where'], $where);
        return $this;
    }

    /**
     * 解析where
     * 'field_name' => [
     *      'connector' => 'AND', # OR
     *      'operator'  => '=', # != like between in notin
     *      'value'     => '', # operator为between时为双值数组 in/notin 时为数组
     * ]
     * @param array $where
     * @return array
     * @throws GeneralException
     */
    protected function parseWhere(array $where): array
    {
        $_where = '';
        $bind = [];
        foreach ($where as $index => $value) {
            if (is_array($value)) {
                $connector = $value['connector'] ?? 'AND'; #
                if (is_numeric($index)) {
                    list($__where, $__bind) = $this->parseWhere($value);
                    $_where .= "{$connector} ( {$__where} )";
                    $bind = array_merge($bind, $__bind);
                } else {
                    $operator = strtoupper($value['operator'] ?? '=');
                    switch ($operator) {
                        case '=':
                        case '!=':
                        case 'LIKE':
                            $_where .= "{$connector} `{$index}` {$operator} ? ";
                            $bind[] = $value['value'];
                            break;
                        case 'BETWEEN':
                            $_where .= "{$connector} `{$index}` {$operator} ? AND ? ";
                            $bind = array_merge($bind, $value['value']); # 两个值丢到bind里面
                            break;
                        case 'IN':
                        case 'NOTIN':
                            $holder = rtrim(str_repeat(' ? ,', count($value['value'])), ',');
                            $_where .= "{$connector} `{$index}` {$operator} ( {$holder} ) ";
                            $bind = array_merge($bind, $value['value']); # 多个值丢到bind里面
                            break;
                        default:
                            throw new GeneralException("invalid operator '$operator'");
                    }
                }
            } else {
                $_where .= "AND `{$index}` = ? ";
                $bind[] = $value;
            }
        }
        return [' ' . ltrim($_where, 'ANDOR'), $bind]; # and or 剔除
    }

    /**
     * 创建SQL语句
     * @param bool $reset 是否重置查询条件
     * @return array 返回SQL语句和输入绑定参数(防注入)
     */
    abstract public function build(bool $reset = true): array;

    /**
     * 重置查询/执行生成器
     * $fields ==> array(
     *     'fieldName' => 'fieldValue',
     * );
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
            'where' => [],//操作的where信息
            'join' => null,
            'group' => '',
            'order' => null,
            'having' => null,
            'limit' => 10,
            'offset' => 0,
        ];
        return $this;
    }

}