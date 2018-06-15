<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/14 0014
 * Time: 11:21
 */

namespace driphp\service;


use Elasticsearch\Client;
use driphp\core\Service;
use Elasticsearch\ClientBuilder;

/**
 * Class ElasticSearch
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html
 *
 * @method ElasticSearch getInstance(array $config = []) static
 * @package driphp\service
 */
class ElasticSearch extends Service
{
    protected $config = [
        # By default, the client will retry n times, where n = number of nodes in your cluster.
        # A retry is only performed if the operation results in a "hard" exception: connection refusal, connection timeout,
        # DNS lookup timeout, etc. 4xx and 5xx errors are not considered retry’able events, since the node returns an operational response.
        #  If all five nodes result in a connection timeout (for example), the client will throw an OperationTimeoutException
        'retries' => 0,
    ];
    /**
     * @var Client
     */
    private $client;

    public function connect(string... $hosts)
    {
        $this->client = ClientBuilder::create()->setHosts($hosts);
        if ($this->config['retries']) {
            $this->client->setRetries((int)$this->config['retries']);
        }
        $this->client = $this->client->build();
        return $this;
    }

    const STATS_INDICES = 1;

    /**
     * @param string $index
     * @param array $settings
     * @param array $mappings
     * @return array
     */
    public function createIndex(string $index, array $settings = [], array $mappings = [])
    {
        return $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => $settings,
                'mappings' => $mappings
            ],
        ]);
    }

    public function stats()
    {
        return $this->client->indices()->stats();
    }

    /**
     * 建立索引
     * To index a document, we need to specify four pieces of information: index, type, id and a document body.
     * This is done by constructing an associative array of key:value pairs. The request body is itself an associative
     * array with key:value pairs corresponding to the data in your document
     *
     * @param string $index
     * @param string $type
     * @param string $id
     * @param array $body
     * @return array
     */
    public function index(string $index, string $type, string $id, array $body): array
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $body,
        ];
        return $this->client->index($params);
    }

    /**
     * Let’s get the document that we just indexed. This will simply return the document:
     *
     * $params = [
     *  'index' => 'my_index',
     *  'type' => 'my_type',
     *  'id' => 'my_id'
     * ];
     *
     * $response = $client->get($params);
     * print_r($response);
     * The response contains some metadata (index, type, etc) as well as a _source field…this is the original document that you sent to Elasticsearch.
     *
     * [
     *  [_index] => my_index
     *  [_type] => my_type
     *  [_id] => my_id
     *  [_version] => 1
     *  [found] => 1
     *  [_source] => [
     *      [testField] => abc
     *  ]
     * ]
     * @param string $index
     * @param string $type
     * @param string $id
     * @return array
     */
    public function get(string $index, string $type, string $id): array
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ];
        return $this->client->get($params);
    }

    /**
     *
     *
     * @param string $index
     * @param string $type
     * @param array $match
     * [
     * 'testField' => 'abc'
     * ]
     * @param array $must
     *  [
     *  [ 'match' => [ 'testField' => 'abc' ] ],
     *  [ 'match' => [ 'testField2' => 'xyz' ] ],
     * ]
     * @param array $filter
     * @param array $should
     * @return array
     */
    public function search(string $index, string $type, array $match, array $must, array $filter, array $should)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                'query' => [
                    'match' => $match,
                    'bool' => [
                        'must' => $must,
                    ],
                ],
            ],
        ];

        return $this->client->search($params);
    }
}