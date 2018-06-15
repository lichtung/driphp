<?php
/**
 * User: linzhv@qq.com
 * Date: 19/03/2018
 * Time: 16:56
 */
declare(strict_types=1);


namespace driphp\core\database;

use driphp\Kernel;
use driphp\throws\core\database\ExecuteException;
use driphp\throws\core\database\RecordNotFoundException;
use driphp\throws\core\database\RecordNotUniqueException;


/**
 * Class ORM  对象关系映射(Object Relational Mapping)
 *
 * 优点：程序中的数据对象自动地转化为关系型数据库中对应的表和列，避免直接接触SQL
 *
 * @package driphp\core\database
 */
abstract class ORM extends Model
{

    /**
     * @var string|array|int
     */
    private $_pk = '';
    /**
     * @var array ORM映射记录的数据
     */
    private $_data = [];
    /**
     * @var bool 通过魔术方法改变了数据的时候设置为true
     */
    private $_flag_modified = false;

    /**
     * @param int $primaryKey
     * @param Dao|null $dao
     * @return mixed
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     */
    public static function instance($primaryKey = 0, Dao $dao = null)
    {
        static $_instances = [];
        if ($primaryKey) {
            $key = (is_array($primaryKey) ? serialize($primaryKey) : $primaryKey) . $dao->drive()->getDSN();
            if (!isset($_instances[$key])) {
                /** @var ORM $object */
                $_instances[$key] = Kernel::factory(static::class, [$primaryKey, $dao]);
            }
            return $_instances[$key];
        } else {
            # 主键为空的情况下，不返回单例
            $className = static::class;
            return new $className($primaryKey, $dao);
        }
    }


    /**
     * 获取全部数据
     * @return array
     */
    public function data(): array
    {
        return $this->_data;
    }

    /**
     * ORM constructor.
     * @param int|string|array $primaryKey
     * @param Dao $dao 数据库访问对象
     */
    public function __construct($primaryKey = 0, Dao $dao = null)
    {
        parent::__construct($dao);
        $this->_pk = $primaryKey;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->_pk = 0;
        $this->_data = [];
        return $this;
    }

    /**
     * 添加记录
     * @param bool $autoReload 添加完成后自动刷新数据（适合有主键的情况下）
     * @return bool
     * @throws ExecuteException
     * @throws RecordNotFoundException
     * @throws RecordNotUniqueException
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\QueryException
     */
    public function insert(bool $autoReload = true): bool
    {
        $data = $this->_data;
        if ($fields = array_keys($data)) {
            $this->_flag_modified = false;
            $dao = $this->dao();

            $data = array_values($data);
            $_fields = '';
            foreach ($fields as $field) {
                $_fields .= $dao->escape($field) . ',';
            }
            $fields = rtrim($_fields, ',');
            $holder = rtrim(str_repeat('?,', count($data)), ',');

            $result = $dao->exec("INSERT INTO `{$this->_table}` ( {$fields} ) VALUES ( {$holder} );",
                    array_values($data)) === 1;
            if (true === $result) {
                # http://php.net/manual/zh/pdo.lastinsertid.php
                # 如果使用了事务，在事务commit之后得到的 lastInsertId 为0，需要在commit之前调用
                if ($lastInsertId = $dao->lastInsertId()) {
                    $this->_pk = $lastInsertId; # 只有自增主键才有
                    $autoReload and $this->find(true);
                }
            }
            return $result;
        } else {
            throw new ExecuteException("No data to insert");
        }
    }

    /**
     * @param array $data
     * @return bool
     * @throws ExecuteException
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     */
    public function update(array $data = []): bool
    {
        $data = $data ?: $this->_data;
        if ($fields = array_keys($data)) {
            $this->_flag_modified = false;
            $dao = $this->dao();

            $_fields = '';
            foreach ($fields as $field) {
                $_fields .= $dao->escape($field) . ' = ? ,';
            }
            $fields = rtrim($_fields, ',');

            list($where, $bind) = $this->_parsePrimaryAsWhere();
            $bind = array_merge(array_values($data), $bind);

            return $dao->exec("UPDATE `{$this->_table}` SET {$fields} WHERE {$where} LIMIT 1;",
                    $bind) === 1;
        } else {
            throw new ExecuteException("No data to update");
        }
    }

    /**
     * 重新加载数据
     * @param bool $force 强制刷新
     * @return $this
     * @throws RecordNotFoundException 记录不存在时抛出
     * @throws RecordNotUniqueException 记录不唯一时抛出
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\QueryException
     */
    public function find(bool $force = false)
    {
        if ($force or empty($this->_data)) {
            list($where, $bind, $raw) = $this->_parsePrimaryAsWhere();
            $sql = "SELECT * FROM {$this->_table} WHERE {$where} LIMIT 2;";
            $list = $this->dao()->query($sql, $bind);
            $count = count($list);
            if ($count !== 1) {
                throw new RecordNotFoundException($raw);
            } elseif ($count === 2) {
                throw new RecordNotUniqueException($raw);
            }
            #  释放主键
            if (is_array($this->primaryKey)) {
                foreach ($this->primaryKey as $item) {
                    unset($list[0][$item]);
                }
            } else {
                unset($list[0][$this->primaryKey]);
            }
            $this->_data = $list[0];
        }
        return $this;
    }

    /**
     * @return bool
     * @throws \driphp\throws\core\ClassNotFoundException
     * @throws \driphp\throws\core\DriverNotDefinedException
     * @throws \driphp\throws\core\database\ConnectException
     * @throws \driphp\throws\core\database\ExecuteException
     */
    public function delete(): bool
    {
        list($where, $bind,) = $this->_parsePrimaryAsWhere();
        $sql = "DELETE FROM {$this->_table} WHERE {$where} LIMIT 1;";
        $result = $this->dao()->exec($sql, $bind) === 1;
        $result and $this->reset();
        return $result;
    }

    /**
     * @return array
     */
    private function _parsePrimaryAsWhere(): array
    {
        if (is_array($this->primaryKey)) {
            return $this->_parseWhere($this->primaryKey);
        } else {
            $raw = [];
            $bind = [$this->_pk];
            $where = "`{$this->primaryKey}` = ?";
            $raw[$this->primaryKey] = $this->_pk;
            return [$where, $bind, $raw];
        }
    }


    public function __get(string $name)
    {
        $value = $this->_data[$name] ?? null;
        if (is_numeric($value)) {
            return (int)$value; # PHP_INT_MIN ~ PHP_INT_MAX
        } else {
            return $value;
        }
    }

    public function __set(string $name, $value): void
    {
        $this->_flag_modified = true;
        $this->_data[$name] = $value;
    }

    /**
     * 调用不存在的方法时 转至 dao对象上调用
     * 需要注意的是，访问了禁止访问的方法时将返回false
     * @param string $name 方法名称
     * @param array $args 方法参数
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->dao(), $name], $args);
    }

}