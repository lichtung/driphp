<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 16:40
 */
declare(strict_types=1);


namespace driphp\core\database;

use PDO;
use driphp\core\database\driver\SQLiteModel;
use driphp\throws\core\database\SqliteException;
use driphp\throws\io\FileWriteException;

class SQLite
{

    protected static $sqlite3 = '';

    protected $dbName = '';

    protected $dbFile = '';

    /**
     * LiteDB constructor.
     * @param string $dbName 数据库名称,如果出现'/'则表示绝对路径
     * @param bool $createIfNotExist
     * @throws FileWriteException 极少出现这样的清空,为缓存无法写入时出现
     * @throws SqliteException
     */
    public function __construct($dbName, $createIfNotExist = false)
    {
        if (!self::$sqlite3) {
            $sqlite = realpath(DRI_IS_WIN ? __DIR__ . '/../../bin/sqlite3.exe' : __DIR__ . '/../../bin/sqlite3');
            if (!is_file($sqlite)) {
                throw new SqliteException("sqlite engine $sqlite not exist");
            } elseif (!is_executable($sqlite)) {
                if (!chmod($sqlite, 0700)) {
                    throw new SqliteException("engine in-executable");
                }
            } else {
                self::$sqlite3 = $sqlite;
            }
        }
        $this->dbName = $dbName;
        $this->dbFile = strpos($dbName, '/') !== false ? $dbName : DRI_PATH_DATA . $dbName . '.db';
        if (!is_file($this->dbFile)) {
            if ($createIfNotExist) {
                $this->createTable(DRI_PROJECT_NAME);
            } else {
                throw new SqliteException("sqlite [$dbName] not found");
            }
        }
    }

    /**
     * 获取数据库文件路径
     * @return string
     */
    public function getDatabasePath()
    {
        return $this->dbFile;
    }

    /**
     * @param string $tableName
     * @param array $fields 键为字段名称,值为属性,如VARCHAR, NOT NULL等
     * @throws FileWriteException
     */
    public function createTable($tableName, array $fields = ['VAL' => 'VARCHAR'])
    {
        $_fields = '';
        foreach ($fields as $name => $type) {
            $_fields .= "{$name} {$type} ,";
        }
        $_fields = trim($_fields, ',');
        # 数据库文件不存在是创建
        $kvSQL = "CREATE TABLE {$tableName} ( ID INT NOT NULL PRIMARY KEY , {$_fields} );";
        //临时SQL文件，用于保存SQL 作为创建的参数
        $sqlFile = DRI_PATH_RUNTIME . 'temp/table.create.' . $tableName . microtime(true) . '.sql';

        is_dir($sql_dir = dirname($this->dbFile)) or mkdir($sql_dir, 0700, true);
        is_dir($sqlFileParentDir = dirname($sqlFile)) or mkdir($sqlFileParentDir, 0700, true);

        if (file_put_contents($sqlFile, $kvSQL)) {
            exec(self::$sqlite3 . " {$this->dbFile} < {$sqlFile}");
        } else {
            throw new FileWriteException($sqlFile);
        }
    }

    /**
     * 获取数据库表列表
     * ```php
     *  [
     *      'stagein'   => [
     *          'type'  => 'table',
     *          'name'  => 'stagein',
     *          'tbl_name'  => 'stagein',
     *          'rootpage'  => '2',
     *          'sql'  => 'CREATE TABLE stagein ( ID INT NOT NULL PRIMARY KEY , VAL VARCHAR  )',
     *      ],
     * ];
     * ```
     * ps:
     * - 对于表来说，type 字段永远是 ‘table’，name 字段永远是表的名字
     * - 对于索引，type 等于 ‘index’, name 则是索引的名字，tbl_name 是该索引所属的表的名字
     *
     * @return array
     * @throws SqliteException
     */
    public function getTables()
    {
        # ps:每次需要重新建立连接,否则抛出异常 "database schema has changed"
        $connection = $this->getConnection();
        $list = $connection->query('SELECT * FROM sqlite_master WHERE type = \'table\';');
        if ($list) {
            $list = $list->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw new SqliteException(var_export($connection->errorInfo(), true));
        }
        $tmp = [];
        foreach ($list as $item) {
            $tmp[$item['name']] = $item;
        }
        return $tmp;
    }

    /**
     * @param $sql
     * @return array
     */
    public function query($sql)
    {
        return $this->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @return PDO
     */
    public function getConnection()
    {
        static $connection = null;
        if (!$connection) {
            $connection = new PDO('sqlite:' . $this->dbFile);
        }
        return $connection;
    }

    /**
     * @param $name
     * @return bool
     * @throws SqliteException
     */
    public function hasTable($name)
    {
        $tables = $this->getTables();
        return isset($tables[$name]);
    }

    /**
     * @param string $className 类名称
     * @return SQLiteModel
     */
    public function createLiteModel(string $className)
    {
        /** @var SQLiteModel $instance */
        $instance = new $className($this->dbFile);
        return $instance;
    }
}