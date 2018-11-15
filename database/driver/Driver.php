<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:17
 */
declare(strict_types=1);


namespace driphp\database\driver;

use PDO;
use PDOException;
use driphp\Component;
use driphp\database\Dao;
use driphp\DriverInterface;
use driphp\throws\database\ConnectException;

/**
 * Class Driver
 *
 *
 *
 * @package driphp\database\driver
 */
abstract class Driver extends PDO implements DriverInterface
{
    /**
     * 配置
     * @var array
     */
    protected $config = [];
    /**
     * @var Dao
     */
    protected $dao = null;
    /**
     * @var string Data Source Name
     */
    protected $dsn = '';

    final public function getDSN(): string
    {
        return $this->dsn;
    }

    /**
     * Driver constructor.
     * @param array $config
     * @param Component|Dao $context
     * @throws ConnectException
     */
    public function __construct(array $config, Component $context)
    {
        $this->dao = $context;
        $config and $this->config = array_merge($this->config, $config);
        $this->dsn = empty($config['dsn']) ? $this->buildDSN($config) : $config['dsn'];
        try {
            parent::__construct(
                $this->dsn,
                $config['user'],
                (string)$config['passwd'], # 密码可能是纯数字
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//默认异常模式
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//结果集返回形式
                    PDO::ATTR_TIMEOUT => 5, # 连接超时时间为5秒，默认30秒
                ]
            );
        } catch (PDOException $e) {
            throw new ConnectException($e->getMessage(), $e->getCode());
        }
    }


    /**
     * compile component to executable sql statement
     * @param array $components
     * @return string
     */
    abstract public function compile(array $components): string;

    /**
     *  transfer word(may be keyword) if not transferred
     * @param string $field
     * @return string
     */
    abstract public function escape(string $field): string;

    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName 数据表名称
     * @return array
     */
    abstract public function getFields(string $tableName): array;

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    abstract public function getTables(string $dbName = ''): array;

    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    abstract public function buildDSN(array $config): string;

    /**
     * 显示所有得数据库
     * @return array
     */
    abstract public function getDatabases(): array;
}