<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:15
 */
declare(strict_types=1);

namespace driphp\database;

use PDO;
use PDOStatement;
use PDOException;
use driphp\Component;
use driphp\database\driver\MySQL;
use driphp\throws\database\ConnectException;
use driphp\throws\database\ExecuteException;
use driphp\throws\database\GeneralException;
use driphp\throws\database\QueryException;
use driphp\database\driver\Driver;
use driphp\throws\ClassNotFoundException;
use driphp\throws\DriverNotFoundException;

/**
 * Class Dao  数据库访问对象(Database Access Object)
 *
 * 出现错误时抛出DatabaseException异常
 *
 * @method string escape($field)
 * @method string compile(array $components)
 * @method array getDatabases()
 * @method array getTables(string $dbName = '')
 *
 *
 * MySQL不支持嵌套事物，在开启事务的情况下再次开启事务会自动提交上一次的事务
 * @method bool commit() commit current transaction
 * @method bool rollback() rollback current transaction
 * @method bool inTransaction()  check if is in a transaction
 *
 *
 * @method int lastInsertId($name = null) get auto-inc id of last insert record
 * @method Dao factory(array $config = []) static
 * @package driphp\core
 */
class Dao extends Component
{
    const ERROR_TRANSACTION_ALREADY_ACTIVE = -10010; # There is already an active transaction

    protected $config = [
        'drivers' => [
            'default' => [
                'name' => MySQL::class,
                'config' => [
                    # 数据源相关
                    'name' => 'test',
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'charset' => 'UTF8',
                    # 用户相关
                    'user' => 'root',
                    'passwd' => '123456',
                    'dsn' => 'mysql:host=127.0.0.1;dbname=test;port=3306;charset=UTF8',//默认先检查差DSN是否正确,直接写dsn而不设置其他的参数可以提高效率，也可以避免潜在的bug
                ],
            ],
        ],
    ];

    protected function initialize()
    {
    }

    /**
     * @param string $index
     * @return Dao
     * @throws ClassNotFoundException
     * @throws DriverNotFoundException
     * @throws ConnectException
     */
    public static function connect(string $index = 'default')
    {
        $dao = self::factory(['index' => $index]);
        $dao->drive($index);
        return $dao;
    }

    /**
     * @param string $index
     * @return \driphp\DriverInterface|Driver
     * @throws ClassNotFoundException
     * @throws DriverNotFoundException
     * @throws ConnectException 数据库连接失败时抛出
     */
    public function drive(string $index = '')
    {
        return parent::drive($index);
    }

    /**
     * @var PDOStatement current PDOStatement object
     */
    protected $_statement = null;

    /**
     * 上一次执行的SQL语句
     * @var array
     */
    protected static $_lastSql = [];
    /**
     * 返回上一次查询的SQL输入参数
     * @var array
     */
    protected static $_lastParams = [];

    /********************************* 基本的查询功能(发生了错误可以查询返回值是否是false,getError可以获取错误的详细信息(每次调用这些功能前都会清空之前的错误)) ***************************************************************************************/
    /**
     * 简单地查询一段SQL，并且将解析出所有的结果集合
     * @param string $sql 查询的SQL
     * @param array $params 输入参数  如果输入参数未设置或者为null（显示声明），则直接查询;如果输入参数为非空数组，则使用PDOStatement对象查询
     * @param bool $fetchAll 是否立即返回全部结果
     * @return array 返回array类型表述查询结果
     * @throws ClassNotFoundException
     * @throws ConnectException
     * @throws DriverNotFoundException
     * @throws QueryException
     */
    public function query(string $sql, array $params = null, bool $fetchAll = true): array
    {
        self::$_lastSql[] = $sql;
        self::$_lastParams[] = $params;
        try {
            if (empty($params)) {
                if ($this->_statement = $this->drive()->query($sql)) {//query成功时返回PDOStatement对象,否则返回false
                    return $fetchAll ? $this->_statement->fetchAll(PDO::FETCH_ASSOC) : [];//成功返回
                } else {
                    throw new QueryException($this->drive());
                }
            } else {
                $this->_statement = $this->drive()->prepare($sql);//可能return false或者抛出错误
                if ($this->_statement->execute($params)) {/*execute不会抛出异常*/
                    return $fetchAll ? $this->_statement->fetchAll(PDO::FETCH_ASSOC) : [];
                } else {
                    throw new QueryException($this->_statement);
                }
            }
        } catch (PDOException $e) {
            throw new QueryException($e, (int)$e->getCode());
        }
    }

    /**
     * 简单地执行Insert、Delete、Update操作
     * @param string $sql 待查询的SQL语句，如果未设置输入参数则需要保证SQL已经被转义
     * @param array $params 输入参数,具体参考query方法的参数二
     * @return int 返回受到影响的行数
     * @throws ExecuteException
     * @throws ConnectException
     * @throws DriverNotFoundException
     * @throws ClassNotFoundException
     */
    public function exec(string $sql, array $params = []): int
    {
        self::$_lastSql[] = $sql;
        self::$_lastParams[] = $params;
        try {
            if (empty($params)) {
                //调用PDO的查询功能
                if (false !== ($rst = $this->drive()->exec($sql))) {
                    return $rst;
                } else {
                    throw new ExecuteException($this->drive());
                }
            } else { //调用PDOStatement的查询功能
                $this->_statement = $this->drive()->prepare($sql);
                if (false !== $this->_statement->execute($params)) {
                    return $this->_statement->rowCount();
                } else {
                    throw new ExecuteException($this->_statement);
                }
            }
        } catch (PDOException $e) {
            throw new ExecuteException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getStatement(): PDOStatement
    {
        return $this->_statement;
    }

    /**
     * 从结果集中获取下一行
     * @param int $fetch_style
     *              \PDO::FETCH_ASSOC 关联数组
     *              \PDO::FETCH_BOUND 使用PDOStatement::bindColumn()方法时绑定变量
     *              \PDO::FETCH_CLASS 放回该类的新实例，映射结果集中的列名到类中对应的属性名
     *              \PDO::FETCH_OBJ   返回一个属性名对应结果集列名的匿名对象
     * @param int $cursor_orientation 默认使用\PDO::FETCH_ORI_NEXT，还可以是PDO::CURSOR_SCROLL，PDO::FETCH_ORI_ABS，PDO::FETCH_ORI_REL
     * @param int $cursor_offset
     *              参数二设置为PDO::FETCH_ORI_ABS(absolute)时，此值指定结果集中想要获取行的绝对行号
     *              参数二设置为PDO::FETCH_ORI_REL(relative) 时 此值指定想要获取行相对于调用 PDOStatement::fetch() 前游标的位置
     * @return array 此函数（方法）成功时返回的值依赖于提取类型。
     * @throws GeneralException
     */
    public function fetch($fetch_style = PDO::FETCH_ASSOC, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        $res = $this->_statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
        if (false === $res) {
            throw new GeneralException($this->_statement);
        }
        return $res;
    }

    /**
     * 开启事务
     * @return bool
     * @throws GeneralException 服务事务关闭了自动提交并且嵌套开启了事务，抛出异常，警告"There is already an active transaction"
     * @throws DriverNotFoundException
     * @throws ClassNotFoundException
     * @throws ConnectException
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->drive()->beginTransaction();
        } catch (PDOException $exception) {
            throw new GeneralException($exception->getMessage());
        }
    }

    /**
     * @param bool $all
     * @return array|string
     */
    final public static function getLastSql($all = false)
    {
        if ($all) {
            return self::$_lastSql;
        } else {
            $last = end(self::$_lastSql);
            return false === $last ? '' : $last;
        }
    }

    /**
     * @param bool $all
     * @return array
     */
    final public static function getLastParams($all = false)
    {
        if ($all) {
            return self::$_lastParams;
        } else {
            $last = end(self::$_lastParams);
            return empty($last) ? [] : $last;
        }
    }


}