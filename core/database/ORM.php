<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 04/09/2018
 * Time: 19:14
 */

namespace driphp\core\database;

use driphp\core\database\builder\Query;
use driphp\throws\database\DataInvalidException;
use driphp\throws\database\exec\DuplicateException;
use driphp\throws\database\ExecuteException;
use driphp\throws\database\FieldInvalidException;
use driphp\throws\database\InsertException;

/**
 * Class ORM 内置对象关系映射 (Object Relational Mapping)
 * @package driphp\core\database
 */
abstract class ORM
{
    /**
     * 数据表前缀
     * @return string
     */
    abstract public function tablePrefix(): string;

    /**
     * 数据表名称
     * @return string
     */
    abstract public function tableName(): string;

    /**
     * 返回存储引擎,默认innodb
     * @return string
     */
    public function tableStorageEngine(): string
    {
        return 'InnoDB';
    }

    /**
     * 主键
     * @return string
     */
    public function primaryKey()
    {
        return 'id';
    }

    /**
     * 复合主键
     * @return array 返回空数组表示不使用复合主键
     */
    public function primaryKeys(): array
    {
        return [];
    }

    /**
     * 数据表结构
     *  返回一个字典(键值对列表):
     *      键为字段名称
     *      值为配置数组,其中:
     *          type    string      表示字段类型,如 int(10) unsigned, varchar(255), timestamp, datetime
     *          notnull boolean     表示是否允许为null
     *          autoinc boolean     表示是否自增,一般用于主键
     *          comment string      表示字段备注
     *          index   boolean     表示是否设置索引,可以加快查询,排序操作,但是会影响修改的速度  @see https://www.cnblogs.com/whgk/p/6179612.html
     *          unique  boolean     表示是否是唯一索引
     *          default string      默认值,如timestamp可以是'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
     *
     *  系统会预定于一些字段,如 id(自增主键),created_at(记录添加时间),updated_at(记录修改时间),deleted_at(记录软删除时间)
     *  用户自定义的同名字段会覆盖这些预定义的配置
     * @return array
     */
    abstract public function structure(): array;

    /** @var Dao */
    private $dao;
    /** @var string 完整表的名称 */
    protected $tableName = '';
    /** @var array */
    protected $data = [];

    final public function __construct(Dao $dao)
    {
        $this->dao = $dao;
        $this->tableName = $this->tablePrefix() . $this->tableName();
    }

    /**
     * 获取数据表的完整名称
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * 获取模型的DAO对象
     * @return Dao
     */
    final public function dao(): Dao
    {
        return $this->dao;
    }

    /** @var Query */
    private $query = null;

    public function query(): Query
    {
        return $this->query = new Query($this);
    }

    /**
     * @return ORM[]
     */
    public function select()
    {
    }

    /**
     * @param int $id
     * @return ORM
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     */
    public function find(int $id)
    {
        list($sql, $bind) = $this->query()->where(['id' => $id])->limit(1)->build();
        $data = $this->dao->query($sql, $bind);
        $orm = new static($this->dao);
        $orm->setData($data[0] ?? []);
        return $orm;
    }

    /**
     * @param array $data
     * @return ORM
     * @throws DuplicateException
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     */
    public function insert(array $data)
    {

        # 默认赋值创建时间和修改时间
        $data['created_at'] = $data['updated_at'] = (new \DateTime('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s');

        $fields = array_keys($data);
        $values = array_values($data);

        $_fields = '';
        foreach ($fields as $field) {
            $_fields .= $this->dao->escape($field) . ',';
        }
        $fields = rtrim($_fields, ',');
        $holder = rtrim(str_repeat('?,', count($values)), ',');

        try {
            if (1 === $this->dao->exec("INSERT INTO `{$this->tableName}` ( {$fields} ) VALUES ( {$holder} );", $values)) {
                $lastInsertId = (int)$this->dao->lastInsertId();
                return $this->find($lastInsertId);
            } else {
                throw new InsertException('Insert failed');
            }
        } catch (ExecuteException $exception) {
            $message = $exception->getMessage();
            if (false !== strpos($message, 'Integrity constraint violation')) {
                throw new DuplicateException($message); # 插入数据重复
            }
            throw $exception;
        }
    }

    /**
     * @return ORM
     */
    public function update()
    {
    }

    /**
     * 软删除一条数据
     * @return bool
     */
    public function delete()
    {

    }

    /**
     * 硬删除
     * @return void
     */
    public function hardDelete()
    {

    }

    /**
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\ExecuteException
     */
    final public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tablePrefix()}{$this->tableName()}` ( 
{$this->_buildStructure()} 
) ENGINE={$this->tableStorageEngine()} DEFAULT CHARSET=utf8;";
        $this->dao()->exec($sql);
    }

    /**
     * @deprecated 删除表是危险操作,仅用于测试
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\ExecuteException
     */
    final public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS `{$this->tableName}`; ";
        $this->dao()->exec($sql);
    }

    /**
     * 创建结构
     * @return string
     */
    private function _buildStructure()
    {
        $structure = '';
        $indexKeys = [];
        $uniqueKeys = [];
        $tableStructure = $this->structure();
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
        if ($primaryKeys = $this->primaryKeys()) {
            $pk = implode('`,`', $primaryKeys);
        } else {
            $pk = $this->primaryKey();
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

    /**
     * 设置数据
     * @param array $data
     * @return $this
     * @throws DataInvalidException 设置的数据不合理时抛出
     */
    public function setData(array $data)
    {
        if (empty($data['id'])) {
            throw new DataInvalidException('data missing id');
        }
        $this->data = $data;
        return $this;
    }

    /**
     * 获取数据
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @param string $name
     * @param $value
     * @throws FieldInvalidException
     */
    public function __set(string $name, $value)
    {
        if (!isset($this->structure()[$name])) {
            throw new FieldInvalidException("field '$name' not found in {$this->tableName}");
        }
        $this->data[$name] = $value;
    }

    /**
     * 序列化,用于数据转储
     * @return string
     */
    final public function __toString(): string
    {
        return json_encode($this->toArray());
    }

}