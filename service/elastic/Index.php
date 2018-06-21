<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/20 0020
 * Time: 17:27
 */

namespace driphp\service\elastic;

use driphp\service\ElasticSearch;
use driphp\throws\ParameterInvalidException;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

class Index
{

    /** @var string 索引名称 */
    private $indexName;
    /** @var ElasticSearch */
    private $context;
    /** @var Client */
    private $client;

    /**
     * Index constructor.
     * @param string $indexName
     * @param ElasticSearch $context
     */
    public function __construct(string $indexName, ElasticSearch $context)
    {
        $this->indexName = $indexName;
        $this->context = $context;
        $this->client = $context->getClient();
    }

    /**
     * 建立索引
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_indexing_documents.html
     * To index a document, we need to specify four pieces of information: index, type, id and a document body.
     * This is done by constructing an associative array of key:value pairs. The request body is itself an associative
     * array with key:value pairs corresponding to the data in your document
     *
     * @param string $type
     * @param string $id 如果为null时,ID将会自动生成
     * @param array $body
     * @return array
     * @throws NoNodesAvailableException 无可用节点时抛出
     */
    public function set(string $type, string $id = null, array $body = []): array
    {
        if (empty($body['created_at'])) {
            $body['created_at'] = $body['updated_at'] = time();
        }
        $params = [
            'index' => $this->indexName,
            'type' => $type,
            'body' => $body,
        ];
        isset($id) and $params['id'] = $id;
        return $this->client->index($params);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_getting_documents.html
     * Let’s get the document that we just indexed.
     * @param string $type
     * @param string $id
     * @return Document
     * @throws Missing404Exception 文档不存在时抛出
     * @throws ParameterInvalidException
     */
    public function get(string $type, string $id): Document
    {
        $params = ['index' => $this->indexName, 'type' => $type, 'id' => $id];
        $res = $this->client->get($params);
        return Document::parseFromItem($res);
    }

    /**
     * 删除文档
     * @param string $type
     * @param string $id
     * @return bool
     * @throws Missing404Exception 文档不存在时抛出
     */
    public function delete(string $type, string $id): bool
    {
        $params = ['index' => $this->indexName, 'type' => $type, 'id' => $id];
        $res = $this->client->delete($params)['found'] ?? 0;
        return $res === 0;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_search_operations.html
     * @param string $type
     * @param array $body
     * @return Document[]
     * @throws BadRequest400Exception 查询时使用了不合法的body
     * @throws ParameterInvalidException
     */
    public function search(string $type = '', array $body = []): array
    {
        $params = ['index' => $this->indexName];
        trim($type) and $params['type'] = $type;
        $body and $params['body'] = $body;
        return Document::parseFromList($this->client->search($params)['hits']['hits'] ?? []);
    }

    /**
     * @param string $field
     * @param $value
     * @param  string $type
     * @return Document[]
     * @throws BadRequest400Exception
     * @throws ParameterInvalidException
     */
    public function match(string $field, $value, string $type = ''): array
    {
        return $this->search($type, [
            'query' => [
                'match' => [
                    $field => $value,
                ],
            ],
        ]);
    }

    /**
     * @param array $range
     * @param string $type
     * @return Document[]
     * @throws BadRequest400Exception
     * @throws ParameterInvalidException
     */
    public function range(array $range, string $type = ''): array
    {
        return $this->search($type, [
            'query' => [
                'range' => $range,
            ],
        ]);
    }
}