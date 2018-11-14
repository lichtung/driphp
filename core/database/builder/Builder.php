<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 16:59
 */

namespace driphp\core\database\builder;

use driphp\core\database\Dao;
use driphp\core\database\ORM;

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
     * 创建SQL语句
     * @return array 返回SQL语句和输入绑定参数(防注入)
     */
    abstract function build(): array;

}