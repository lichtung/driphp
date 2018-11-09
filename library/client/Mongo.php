<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 14:45
 */

namespace driphp\library\client;

use stdClass;
use driphp\Component;
use driphp\library\client\mongo\NotFoundException;
use driphp\library\client\mongo\WriteException;
use driphp\library\client\mongo\IterateHandlerInterface;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Exception\AuthenticationException;
use MongoDB\Driver\Exception\ConnectionException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\WriteError;
use MongoDB\Driver\WriteResult;

/**
 * Class Mongo
 *
 * php5 和 php7 使用不同的接口
 *
 * PHPv7 @see http://php.net/manual/zh/book.mongodb.php
 * PHPv5 @see http://php.net/manual/zh/mongo.core.php
 *
 * @method Mongo factory(array $config = []) static
 * @package driphp\library\client
 */
class Mongo extends Component
{
    /** @var array 配置 */
    protected $config = [
        'user' => '', # 只有密码不同时为空时才是有效的
        'password' => '', # 密码不建议是纯数字
        'host' => '127.0.0.1',
        'port' => 27017,
        'database' => 'default', # 默认数据库
        'collection' => 'default', # 默认集合
        'timeout' => 10000,# 毫秒计超时时间(默认10秒)
    ];
    /** @var string 连接URI */
    private $uri = '';
    /** @var Manager */
    private $adapter = null;

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return Manager
     */
    public function getAdapter(): Manager
    {
        return $this->adapter;
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        $this->uri = self::buildURI($this->config);
        $this->adapter = new Manager($this->uri);
    }

    /**
     * 创建连接URI
     * @param array $config
     * @return string
     */
    public static function buildURI(array $config): string
    {
        $uri = 'mongodb://';
        if (!empty($config['user']) and !empty($config['password'])) {
            $uri .= "{$config['user']}:{$config['password']}@";
        }
        $uri .= "{$config['host']}:{$config['port']}"; # 数据库不存在时抛出错误 '/{$config['database']}' MongoDB\Driver\Exception\AuthenticationException: Authentication failed.
        return $uri;
    }

    /**
     * 获取集合名称
     * @return string
     */
    public function getCollection(): string
    {
        return $this->config['database'] . '.' . $this->config['collection'];
    }

    /**
     * 遍历对象
     * @param array $where
     * @param IterateHandlerInterface $handler
     * @param array $options
     * @return void
     * @throws AuthenticationException if authentication is needed and fails
     * @throws ConnectionException if connection to the server fails for other then authentication reasons
     * @throws RuntimeException on other errors (invalid command, command arguments, ...)
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function iterate(array $where, IterateHandlerInterface $handler, array $options = [])
    {
        $query = new Query($where, $options);
        /** @var Cursor $cursor */
        $cursor = $this->adapter->executeQuery($this->getCollection(), $query);
        foreach ($cursor as $document) {
            if (!$handler->handle(self::object2array($document))) break;
        }
    }

    /**
     * @param array $where
     * @param array $options
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function select(array $where, array $options = []): array
    {
        $list = [];
        $where['deleted_at'] = ['$eq' => ''];
        $query = new Query($where, $options);
        /** @var Cursor $cursor */
        $cursor = $this->adapter->executeQuery($this->getCollection(), $query);
        foreach ($cursor as $document) {
            $list[] = self::object2array($document);
        }
        return $list;
    }


    /**
     * @param array $where
     * @param array $options
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     * @throws NotFoundException
     */
    public function find(array $where, array $options = [])
    {
        $list = $this->select($where, $options);
        if (count($list) > 0) {
            return $list[0];
        }
        throw new NotFoundException();
    }

    /**
     * 插入单条数据
     * @param array ...$data
     * @return string 返回文档ID值 "5be51002e1382348e34badf3"
     * @throws WriteException
     */
    public function insert(array $data): string
    {
        $bulk = new BulkWrite();
        $date = date('Y-m-d H:i:s');;
        unset($data['_id']); # _id 不允许手动指定
        empty($data['created_at']) and $data['created_at'] = $date;
        empty($data['updated_at']) and $data['updated_at'] = $date;
        $data['deleted_at'] = '';
        $objectId = $bulk->insert($data);
        $this->_insert($bulk);
        return (string)$objectId;
    }

    /**
     * @param BulkWrite $bulk
     * @throws WriteException 插入数据时指定的ID若存在会报错：E11000 duplicate key error collection: db.collection index: _id_ dup key: { : 2 }
     *  $bulk->insert(['_id' => 1]);
     *  $bulk->insert(['_id' => 2]);
     *  $bulk->insert(['_id' => 2]);
     *  $bulk->insert(['_id' => 3]);
     */
    private function _insert(BulkWrite $bulk)
    {
        /** @var WriteResult $result */
        $result = $this->adapter->executeBulkWrite($this->getCollection(), $bulk, new WriteConcern(WriteConcern::MAJORITY, $this->config['timeout']));
        self::checkWriteError($result);
    }

    /**
     * 插入多条数据
     * @param array ...$data
     * @return void
     * @throws WriteException 插入数据时指定的ID若存在会报错：E11000 duplicate key error collection: db.collection index: _id_ dup key: { : 2 }
     *  $bulk->insert(['_id' => 1]);
     *  $bulk->insert(['_id' => 2]);
     *  $bulk->insert(['_id' => 2]);
     *  $bulk->insert(['_id' => 3]);
     */
    public function inserts(array... $data)
    {
        $bulk = new BulkWrite();
        foreach ($data as $item) {
            $bulk->insert($item);
        }
        $this->_insert($bulk);
    }

    /**
     * 更新一个文档
     * @see https://docs.mongodb.com/manual/reference/method/db.collection.updateOne/#db.collection.updateOne
     * @param array $filter 文档筛选
     * @param array $update 文档更新字段
     * @param array $options 文档更新选项
     * @return int
     * @throws WriteException
     */
    public function updateOne(array $filter, array $update, array $options = [])
    {
        return $this->update($update, $filter, true, $options);
    }

    /**
     * 软删除一个文档
     * @param array $filter
     * @return int
     * @throws WriteException
     */
    public function removeOne(array $filter)
    {
        return $this->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ], $filter, true);
    }

    /**
     * 批量软删除一个文档
     * @param array $filter
     * @return int
     * @throws WriteException
     */
    public function removeMany(array $filter)
    {
        return $this->update([
            'deleted_at' => date('Y-m-d H:i:s')
        ], $filter, false);
    }

    /**
     * 批量更新文档
     * @see https://docs.mongodb.com/manual/reference/method/db.collection.updateMany/
     * @param array $filter 文档筛选
     * @param array $update 文档更新字段
     * @param array $options 文档更新选项
     * @return int
     * @throws WriteException
     */
    public function updateMany(array $filter, array $update, array $options = [])
    {
        return $this->update($update, $filter, false, $options);
    }

    /**
     * 更新文档
     * @param array $fields 更新字段
     * @param array $where where条件
     * @param bool $justOne 只更新一条,相当于 limit 1
     * @param array $options 更新配置
     * @return int
     * @throws WriteException
     */
    public function update(array $fields, array $where, $justOne = true, array $options = [])
    {
        $bulk = new BulkWrite();
        $fields['updated_at'] = date('Y-m-d H:i:s');

        $where['deleted_at'] = ['$eq' => ''];
        $bulk->update($where, [
            '$set' => $fields,
        ], $options ?: [
            # Update only the first matching document if FALSE, or all matching documents TRUE. 为false时最多只更新一条数据
            # This option cannot be TRUE if newObj is a replacement document.
            'multi' => !$justOne,
            # If filter does not match an existing document, insert a single document. 未匹配记录时增加记录
            # The document will be created from newObj if it is a replacement document (i.e. no update operators);
            # otherwise, the operators in newObj will be applied to filter to create the new document.
            'upsert' => false,
        ]);
        /** @var WriteResult $result */
        $result = $this->adapter->executeBulkWrite($this->getCollection(), $bulk, new WriteConcern(WriteConcern::MAJORITY, 1000));
        self::checkWriteError($result);
        return (int)$result->getModifiedCount(); # 返回null表示更新未确认
    }

    /**
     * @param array $filter
     * @param array $options
     * @return int
     * @throws WriteException
     */
    public function deleteOne(array $filter, array $options = [])
    {
        $options['limit'] = 1;
        return $this->delete($filter, $options);
    }

    /**
     * @param array $filter
     * @param array $options
     * @return int
     * @throws WriteException
     */
    public function deleteMany(array $filter, array $options = [])
    {
        $options['limit'] = 0;
        return $this->delete($filter, $options);
    }

    /**
     * @see Mongo::remove 软删除
     * @param array $where
     * @param array $options
     * @return int
     * @throws WriteException
     */
    public function delete(array $where, array $options = [])
    {
        $bulk = new BulkWrite();
        $bulk->delete($where, $options); # limit 为 1 时，删除第一条匹配数据; limit 为 0 时，删除所有匹配数据
        $result = $this->adapter->executeBulkWrite($this->getCollection(), $bulk, new WriteConcern(WriteConcern::MAJORITY, 1000));
        self::checkWriteError($result);
        return (int)$result->getDeletedCount();
    }

    /**
     * @param WriteResult $result
     * @return void
     * @throws WriteException
     */
    public static function checkWriteError(WriteResult $result)
    {
        /** @var WriteError[] $errors */
        $errors = $result->getWriteErrors();
        if ($errors) {
            $error = '';
            foreach ($errors as $error) {
                $error .= $error->getMessage() . PHP_EOL;
            }
            throw new WriteException($error);
        }
    }

    /**
     * executeQuery返回的浮标遍历时的数据
     * @see \MongoDB\Driver\Manager::executeQuery
     * @param stdClass|array|int|string $obj
     * @param int $_level
     * @return array|int|string
     */
    public static function object2array($obj, int $_level = 0)
    {
        if ($_level > 500) return (array)$obj; # 过度嵌套,直接返回
        if (is_object($obj)) { # 转array
            $obj = (array)$obj;
            foreach ($obj as $key => $val) {
                $obj[$key] = self::object2array($val, $_level + 1);
            }
        } elseif (is_array($obj)) { # 遍历,每个元素转array
            $tmp = [];
            foreach ($obj as $item) {
                $tmp[] = self::object2array($item, $_level + 1);
            }
            $obj = $tmp;
        }# 其他类型完整返回
        return $obj;
    }

}