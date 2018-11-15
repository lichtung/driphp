<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 16:59
 */

namespace driphp\database\builder;

use driphp\database\Dao;
use driphp\database\ORM;
use driphp\throws\database\QueryException;

/**
 * Class Builder 生成器
 * @package driphp\database\builder
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
    }


    /**
     * @param array $fields
     * @return $this
     * @throws QueryException
     */
    public function fields(array $fields)
    {
        $structure = $this->context->structure();
        foreach ($fields as $index => $item) {
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
        $this->builder['where'] = $where;
        return $this;
    }

    /**
     * 创建SQL语句
     * @return array 返回SQL语句和输入绑定参数(防注入)
     */
    abstract public function build(): array;

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