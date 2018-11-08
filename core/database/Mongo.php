<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 20:17
 */
declare(strict_types=1);


namespace driphp\core\database;

use MongoClient;
use MongoDB;
use MongoCollection;
use MongoCursor;
use MongoException;
use MongoConnectionException;
use MongoCursorException;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\WriteResult;
use MongoDB\Driver\WriteError;
use MongoDB\Driver\Query;
use MongoDB\Driver\Cursor as Cursor7;
use driphp\Component;
use driphp\throws\core\database\mongo\CollectionNotSelectException;
use driphp\throws\core\database\mongo\DatabaseNotSelectException;
use driphp\throws\core\database\mongo\PersistException;
use driphp\throws\core\database\mongo\UpdateException;

/**
 * Class Mongo
 *
 * php5 和 php7 使用不同的接口
 *
 * PHPv7 @see http://php.net/manual/zh/book.mongodb.php
 * PHPv5 @see http://php.net/manual/zh/mongo.core.php
 *
 * @method Mongo getInstance(string $config = []) static
 *
 * @package dripex\database
 */
class Mongo extends Component
{

    const ITERATOR_BREAK = 0; # 中断遍历
    const ITERATOR_CONTINUE = 1;  # 继续遍历

    protected $config = [
        'scheme' => 'mongodb',
        'user' => '',
        'pass' => '',
        'host' => '127.0.0.1',
        'port' => 27017,
        'path' => '/gtarcade',

        'dsn' => '',
    ];

    /**
     * @var string
     */
    protected $currentDatabaseName = '';
    /**
     * @var MongoDB
     */
    protected $currentDatabase = null;
    /**
     * @var string
     */
    protected $currentCollectionName = '';
    /**
     * @var MongoCollection
     */
    protected $currentCollection = null;
    /**
     * @var bool
     */
    protected $isv7 = true;

    /**
     * @var MongoClient|Manager
     */
    protected $_adapter = null;

    /**
     * 穿件连接
     * @return Component|void
     * @throws MongoConnectionException
     */
    protected function initialize()
    {
        $this->isv7 = version_compare(PHP_VERSION, '7.0', '>=');
        if (!empty($this->config['dsn'])) {
            $server = $this->config['dsn'];
            $this->config = array_merge($this->config, parse_url($server));
        } else {
            # TODO
            $server = $this->createUri($this->config);
        }
        $this->currentDatabaseName = trim($this->config['path'], '/');

        if ($this->isv7) {
            # PHPv7
            $this->_adapter = new Manager($server);
        } else {
            # PHPv5
            $this->_adapter = new MongoClient($server);
            $this->database($this->currentDatabaseName);
        }
    }

    private function createUri(array $config): string
    {
        $url = ($config['scheme'] ?? 'mongodb') . '://';
        if ($config['user']) {
            $url .= $config['user'];
            if ($config['pass']) {
                $url .= ':' . $config['pass'] . '@';
            } else {
                $url .= '@';
            }
        }
        $url .= "{$config['host']}:{$config['port']}";
        if (!empty($config['path'])) {
            if (strpos($config['path'], '/') !== 0) {
                $config['path'] = '/' . $config['path'];
            }
            $url .= $config['path'];
        }
        return $url;
    }

    /**
     * 选择数据库
     * @param string|null $dbName
     * @return Mongo
     */
    public function database($dbName = null)
    {
        $this->currentDatabaseName = $dbName;
        $this->currentCollectionName = '';
        if (!$this->isv7) {
            $this->currentDatabase = $this->_adapter->{$this->currentDatabaseName};
            $this->currentCollection = null;
        }
        return $this;
    }

    /**
     * 选择集合
     * @param string $collectName
     * @return Mongo
     */
    public function collection($collectName)
    {
        $this->currentCollectionName = $collectName;
        $this->isv7 or $this->currentCollection = $this->currentDatabase->createCollection($this->currentCollectionName);
        return $this;
    }

    /**
     * 检查数据库和集合是否选择
     * @return string 返回PHPv7下数据库的[数据库·集合]地址
     * @return string
     * @throws CollectionNotSelectException
     * @throws DatabaseNotSelectException
     */
    public function getDatabaseCollection()
    {
        if ($this->isv7) {
            if (!$this->currentDatabaseName) {
                throw new DatabaseNotSelectException();
            } elseif (!$this->currentCollectionName) {
                throw new CollectionNotSelectException();
            }
        } else {
            if (!isset($this->currentDatabase)) {
                throw new DatabaseNotSelectException();
            } elseif (!isset($this->currentCollection)) {
                throw new DatabaseNotSelectException();
            }
        }
        return $this->currentDatabaseName . '.' . $this->currentCollectionName;
    }

    /**
     * 遍历查找
     * @param callable $iterator
     * @param array $where
     * @param array $options
     * @return void
     * @throws CollectionNotSelectException
     * @throws DatabaseNotSelectException
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function iterate(callable $iterator, array $where = [], array $options = [])
    {
        $dc = $this->getDatabaseCollection();
        if ($this->isv7) {
            $query = new MongoDB\Driver\Query($where, $options);
            /** @var Cursor $cursor */
            $cursor = $this->_adapter->executeQuery($dc, $query);
        } else {
            /** @var MongoCursor $cursor */
            $cursor = $this->currentCollection->find();
        }
        foreach ($cursor as $document) {
            $res = call_user_func_array($iterator, [(array)$document]); # v7 stdClass -> array
            if (self::ITERATOR_BREAK === $res) {
                break;
            } elseif (self::ITERATOR_CONTINUE) {
                continue;
            }
        }
    }

    /**
     * @param WriteResult $result
     * @return void
     * @throws PersistException
     */
    private function _checkWriteError(WriteResult $result)
    {
        /** @var WriteError[] $errors */
        $errors = $result->getWriteErrors();
        if ($errors) {
            $error = '';
            foreach ($errors as $error) {
                $error .= $error->getMessage() . ' ';
            }
            throw new PersistException($error);
        }
    }

    /**
     * 插入数据
     * 发送到数据库的所有字符串必须是 UTF-8 的。
     * 如果有字符串不是 UTF-8，将会抛出 MongoException 异常。
     * 要插入（或者查询）一个非 UTF-8 的字符串，请使用 MongoBinData。
     *
     * 【X】(不成立) 调用 MongoCollection::insert 时设置了 w 后，插入两个具有相同 _id 的元素时，导致抛出 MongoCursorException 的例子
     * @param array[] $data
     * @_param bool $unique 是否要求数据唯一
     * @throws DatabaseNotSelectException
     * @throws CollectionNotSelectException
     * @throws PersistException 插入数据时指定了ID，会报错：E11000 duplicate key error collection: db.collection index: _id_ dup key: { : 2 }
     *  $bulk->insert(['_id' => 1]);
     *  $bulk->insert(['_id' => 2]);
     *  $bulk->insert(['_id' => 2]);
     *  $bulk->insert(['_id' => 3]);
     */
    public function insert(array... $data)
    {
        $dc = $this->getDatabaseCollection();
        if ($this->isv7) {
            $bulk = new BulkWrite();
            foreach ($data as $item) {
                $bulk->insert($item);
            }
            /** @var WriteResult $result */
            $result = $this->_adapter->executeBulkWrite($dc, $bulk, new WriteConcern(WriteConcern::MAJORITY, 1000));
            $this->_checkWriteError($result);
        } else {
            try {
                # v5只能插入一个
                $res = $this->currentCollection->insert($data[0], [
                    'w' => 1,# write concern
                ]);
                if ($res['err'] !== null) {
                    throw new PersistException($res['errmsg']);
                }
            } catch (MongoCursorException $exception) {
                throw new PersistException($exception->getMessage());
            } catch (MongoException $exception) {
                throw new PersistException($exception->getMessage());
            }
        }
    }

    /**
     * 更新结果：
     * array (
     *  'n' => 1,
     *  'nModified' => 1, # 未修改时为0
     *  'ok' => 1,
     *  'err' => NULL,
     *  'errmsg' => NULL,
     *  'updatedExisting' => true,
     * )
     * @param array $fields 设置字段列表
     * @param array $where where条件
     * @param bool $justOne 只更新一条数据
     * @return int 返回更新数量
     * @throws CollectionNotSelectException
     * @throws DatabaseNotSelectException
     * @throws MongoCursorException
     * @throws PersistException
     * @throws UpdateException
     */
    public function update(array $fields, array $where, $justOne = true)
    {
        $dc = $this->getDatabaseCollection();
        if ($this->isv7) {
            $bulk = new BulkWrite();
            $bulk->update($where, [
                '$set' => $fields,
            ], [
                # Update only the first matching document if FALSE, or all matching documents TRUE. 为false时最多只更新一条数据
                # This option cannot be TRUE if newObj is a replacement document.
                'multi' => !$justOne,
                # If filter does not match an existing document, insert a single document. 未匹配记录时增加记录
                # The document will be created from newObj if it is a replacement document (i.e. no update operators);
                # otherwise, the operators in newObj will be applied to filter to create the new document.
                'upsert' => false,
            ]);
            /** @var WriteResult $result */
            $result = $this->_adapter->executeBulkWrite($dc, $bulk, new WriteConcern(WriteConcern::MAJORITY, 1000));
            $this->_checkWriteError($result);
            return (int)$result->getModifiedCount();
        } else {
            $res = $this->currentCollection->update($where, ['$set' => $fields], ['w' => 1, 'multiple' => !$justOne]);
            if (isset($res['errmsg'])) {
                throw new UpdateException($res['errmsg']);
            }
            return isset($res['nModified']) ? intval($res['nModified']) : 0;
        }
    }

    /**
     *
     * 测试删除
     * @param array $where
     * @param bool $justOne
     * @return int 返回删除的条数
     * @throws MongoCursorException
     * @throws MongoException
     * @throws \MongoCursorTimeoutException
     */
    public function remove(array $where, $justOne = true)
    {
        $dc = $this->getDatabaseCollection();
        if ($this->isv7) {
            $bulk = new BulkWrite();
            $bulk->delete($where, ['limit' => $justOne ? 1 : 0]); # limit 为 1 时，删除第一条匹配数据; limit 为 0 时，删除所有匹配数据
            $result = $this->_adapter->executeBulkWrite($dc, $bulk, new WriteConcern(WriteConcern::MAJORITY, 1000));
            $this->_checkWriteError($result);
            return (int)$result->getDeletedCount();
        } else {
            $res = $this->currentCollection->remove($where, [
                'justOne' => $justOne,
            ]);
            if (isset($res['errmsg'])) {
                throw new MongoException($res['errmsg']);
            }
            return isset($res['n']) ? intval($res['n']) : 0;
        }
    }

    /**
     * 查询一个结果
     * @param array $where
     * @param array $options
     * @return array
     * @throws CollectionNotSelectException
     * @throws DatabaseNotSelectException
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function find(array $where, array $options = [])
    {
        $dc = $this->getDatabaseCollection();
        if ($this->isv7) {
            /** @var MongoCursor $cursor */
            $query = new Query($where, $options);
            /** @var Cursor7 $cursor */
            $cursor = $this->_adapter->executeQuery($dc, $query);
            foreach ($cursor as $document) {
                return (array)$document;
            }
        } else {
            /** @var MongoCursor $cursor */
            $cursor = $this->currentCollection->find($where);
            foreach ($cursor as $document) {
                return $document;
            }
        }
        return [];
    }

    /**
     * 查询全部结果
     * @param array $where
     * @param array $options
     * @return array[]
     * @throws CollectionNotSelectException
     * @throws DatabaseNotSelectException
     * @throws MongoDB\Driver\Exception\Exception
     */
    public function findAll(array $where, array $options = [])
    {
        $list = [];
        $dc = $this->getDatabaseCollection();
        if ($this->isv7) {
            /** @var MongoCursor $cursor */
            $query = new Query($where, $options);
            /** @var Cursor7 $cursor */
            $cursor = $this->_adapter->executeQuery($dc, $query);
            foreach ($cursor as $document) {
                $list[] = (array)$document;
            }
        } else {
            /** @var MongoCursor $cursor */
            $cursor = $this->currentCollection->find($where);

            foreach ($cursor as $document) {
                $list[] = $document;
            }
        }
        return $list;
    }


    /**
     * 删除数据库
     * @return bool
     */
    public function drop()
    {
        if ($this->isv7) {
            #  使用命令删除数据库
            return true;
        } else {
            $res = $this->currentDatabase->drop(); # 返回 ['dropped' => '数据库名称', 'ok' => 1,]
            return intval($res['ok']) === 1;
        }
    }

    /**
     * 获取上一次发生的错误
     * @return string
     */
    public function getLastError()
    {
        $error = $this->currentDatabase->lastError();
        return isset($error['err']) ? $error['err'] : '';
    }

}