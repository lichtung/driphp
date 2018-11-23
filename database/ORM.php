<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 04/09/2018
 * Time: 19:14
 */

namespace driphp\database;

use driphp\core\Kits;
use driphp\database\orm\Delete;
use driphp\database\orm\Insert;
use driphp\database\orm\Query;
use driphp\database\orm\Structure;
use driphp\database\orm\Update;
use driphp\throws\database\DataInvalidException;
use driphp\throws\database\exec\DuplicateException;
use driphp\throws\database\ExecuteException;
use driphp\throws\database\FieldInvalidException;
use driphp\throws\database\NotFoundException;
use driphp\throws\database\ValidateException;
use Symfony\Component\Validator\Validation;

/**
 * Class ORM 内置对象关系映射 (Object Relational Mapping)
 * @property int $id 主键ID
 * @property string $created_at 创建时间，无法手动设置
 * @property string $updated_at 修改时间，无法手动设置
 * @property string $deleted_at 删除时间，为null时表示未删除，调用delete
 * @package driphp\core\database
 */
abstract class ORM
{
    /**
     * 返回数据库名称
     * @return string
     */
    public function databaseName(): string
    {
        return '';
    }

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
    public function primaryKey(): string
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
     *          foreign array       表示外键
     *                  table       string  参考表名称，不含前缀
     *                  field       string  参考字段，不能为空
     *                  prefix      string  参考表的前缀
     *                  ondelete    string  父表删除时子表（本表）对应的操作,默认为 CASCADE
     *                  onupdate    string  父表修改时子表（本表）对应的操作,默认为 RESTRICT
     *
     *  系统会预定于一些字段,如 id(自增主键),created_at(记录添加时间),updated_at(记录修改时间),deleted_at(记录软删除时间)
     *  用户自定义的同名字段会覆盖这些预定义的配置
     * @return array
     */
    abstract public function structure(): array;

    /**
     * 默认字段
     * @return array
     */
    public function definedFields(): array
    {
        return [
            'id' => [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'autoinc' => true,
                'comment' => '主键',
            ],
            'created_at' => [
                'type' => 'datetime',
                'notnull' => true,
                'comment' => '记录添加时间',
            ],
            'updated_at' => [
                'type' => 'datetime',
                'notnull' => true,
                'comment' => '记录修改时间',
            ],
            'deleted_at' => [
                'type' => 'datetime',
                'notnull' => false,
                'comment' => '记录软删除时间,为null时候表示已经删除',
                'default' => null,
            ],
        ];
    }

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
     * @param array $where
     * @param int $limit 数量限制,为0表示不限制
     * @return ORM[] 返回ORM字典,键为记录ID，值为对应的ORM对象
     * @throws DataInvalidException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     * @throws \driphp\throws\database\GeneralException
     */
    public function select(array $where, int $limit = 0)
    {
        return $this->query()->where($where)->limit($limit)->fetchAll();
    }

    /**
     * 查找一条数据会返回一个新的对象,带有插入数据的信息
     * @param int $id
     * @return ORM
     * @throws DataInvalidException
     * @throws NotFoundException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\GeneralException
     * @throws \driphp\throws\database\QueryException
     */
    public function find(int $id)
    {
        return $this->query()->where(['id' => $id])->limit(1)->fetch();
    }

    /**
     * 插入数据后会返回一个新的对象,带有插入数据的信息
     * @param array $data
     * @return ORM
     * @throws DataInvalidException
     * @throws DuplicateException
     * @throws ExecuteException
     * @throws NotFoundException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     * @throws \driphp\throws\database\GeneralException
     * @throws ValidateException 验证不通过抛出
     */
    public function insert(array $data = [])
    {
        if (empty($data)) $data = $this->newValues;
        try {
            $this->validate($data);
            $lastInsertId = (new Insert($this))->fields($data)->exec();
            return $this->find($lastInsertId);
        } catch (ExecuteException $exception) {
            $message = $exception->getMessage();
            if (false !== strpos($message, 'Integrity constraint violation')) {
                throw new DuplicateException($message); # 插入数据重复
            }
            throw $exception;
        }
    }

    /**
     * @param array $fields 更新字段字典
     * @return int 更新影响条数
     * @throws DataInvalidException
     * @throws ExecuteException
     * @throws NotFoundException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     * @throws \driphp\throws\database\GeneralException
     * @throws ValidateException
     */
    public function update(array $fields = []): int
    {
        $fields or $fields = $this->newValues;
        $this->validate($fields);
        $count = (new Update($this))->where(['id' => $this->id])->fields($fields)->exec();
        $count and $this->setData($this->find($this->id)->toArray());
        return $count;
    }

    /**
     * 软删除一条数据
     * @return bool
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     */
    public function delete(): bool
    {
        return (new Update($this))->where(['id' => $this->id])->fields(['deleted_at' => Kits::getLocalDatetime()])->exec() > 0;
    }

    /**
     * 硬删除一条数据
     * @return bool
     * @throws ExecuteException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     */
    public function hardDelete(): bool
    {
        return (new Delete($this))->where(['id' => $this->id])->exec() > 0;
    }

    /**
     * @return void
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\ExecuteException
     */
    final public function install()
    {
        list($sql, $bind) = (new Structure($this))->build();
        $this->dao()->exec($sql, $bind);
        $this->onInstalled();
    }

    /**
     * 安装时调起
     * @return void
     */
    protected function onInstalled()
    {
    }

    /**
     * @return bool
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     */
    public function installed(): bool
    {
        return count($this->dao()->query('SHOW TABLES LIKE ?;', [$this->tableName])) === 1;
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
        $this->dao()->exec("DROP TABLE IF EXISTS `{$this->tableName}` ;");
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
        # 如果有数据则保存到 oldValues 中作为原先的值
        if (isset($this->data[$name])) {
            $this->oldValues[$name] = $this->data[$name];
        }
        $this->newValues[$name] = $this->data[$name] = $value;
    }

    /** @var array 修改或者插入的字段 */
    private $newValues = [];
    /** @var array 修改之前的数据 */
    private $oldValues = [];

    /**
     * @return array
     */
    public function getNewValues(): array
    {
        return $this->newValues;
    }

    /**
     * @return array
     */
    public function getOldValues(): array
    {
        return $this->oldValues;
    }

    /**
     * 重置修改,将旧的值替换回原来的位置
     * @return $this
     */
    final public function reset()
    {
        foreach ($this->oldValues as $name => $value) {
            $this->data[$name] = $value;
        }
        $this->oldValues = $this->newValues = [];
        return $this;
    }

    /**
     * 序列化,用于数据转储
     * @return string
     */
    final public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @return string
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\QueryException
     */
    public function generateDocumentDescription(): string
    {
        $list = $this->dao()->describe($this->tableName);
        $properties = '';
        $map = [
            'char' => 'string', # char varchar
            'test' => 'string', # char varchar
            'datetime' => '\Datetime|string', # char datetime
            'timestamp' => '\Datetime|string', # char datetime
            'date' => 'string', # date
            'int' => 'int', # int tinyint smallint
        ];
        foreach ($list as $item) {
            $field = $item['Field'];
            if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;
            $type = $item['Type'];
            $t = 'mixed';
            $comment = str_replace("\n", ' ', $item['Comment'] ?? '');
            foreach ($map as $key => $val) {
                if (false !== stripos($type, $key)) {
                    $t = $val;
                    break;
                }
            }
            $properties .= "@property {$t} \${$field} {$comment}\n";
        }
        return $properties;
    }

    private static $_validator = null;

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    final protected function getValidator()
    {
        if (self::$_validator === null) {
            class_exists(Validation::class, false) or require_once(__DIR__ . '/../vendor/autoload.php');
            self::$_validator = Validation::createValidator();
        }
        return self::$_validator;
    }

    /**
     * @param array $data
     * @throws ValidateException
     */
    final protected function validate(array $data)
    {
        $validation = $this->validation();
        if ($validation) {
            $validator = $this->getValidator();
            foreach ($validation as $name => $rule) {
                if (isset($data[$name])) {
                    $violations = $validator->validate($data[$name], $rule);
                    if (0 !== count($violations)) {
                        // there are errors, now you can show them
                        foreach ($violations as $violation) {
                            throw new ValidateException($violation->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * 定义模型时返回的验证规则
     * @see https://symfony.com/doc/current/validation.html
     * @return array
     */
    abstract protected function validation(): array;

}