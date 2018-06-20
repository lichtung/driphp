<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/14 0014
 * Time: 11:21
 */

namespace driphp\service;

use driphp\service\elastic\Index;
use Elasticsearch\Client;
use driphp\core\Service;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;

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
        'retries' => 1,
    ];
    /**
     * @var Client
     */
    private $client;

    public function getClient()
    {
        return $this->client;
    }

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
     * 判断Index是否存在
     * @param string $index
     * @return bool
     */
    public function exist(string $index): bool
    {
        return $this->client->indices()->exists(['index' => $index]);
    }

    /**
     * @param string $index
     * @param array $settings
     * @param array $mappings
     * @return array
     */
    public function create(string $index, array $settings = [], array $mappings = [])
    {
        $body = [];
        $settings and $body['settings'] = $settings;
        $mappings and $body['mappings'] = $mappings;
        return $this->client->indices()->create([
            'index' => $index,
            'body' => $body,
        ]);
    }

    /**
     * 删除索引(注:收到了请求并不表示删除成功)
     * @param string $index
     * @return bool
     * @throws Missing404Exception 索引不存
     */
    public function delete(string $index): bool
    {
        try {
            $res = $this->client->indices()->delete([
                'index' => $index,
            ]);
            return $res['acknowledged'] ?? false;
        } catch (Missing404Exception $exception) {
            if (strpos($exception->getMessage(), 'index_not_found_exception')) {
                return false;
            }
            throw $exception;
        }
    }

    /**
     * @param string $index
     * @return Index
     */
    public function index(string $index): Index
    {
        static $_instances = [];
        if (!isset($_instances[$index])) {
            $this->create($index, [], []);
            $_instances[$index] = new Index($index, $this);
        }
        return $_instances[$index];
    }

    /**
     * [
     *  'indices'=>[
     *      'index_1'=>...,
     *      'index_2'=>...,
     *  ]
     * ]
     * @return array
     */
    public function stats(): array
    {
        return $this->client->indices()->stats();
    }

    /**
     * @return array
     */
    public function getIndices(): array
    {
        return $this->stats()['indices'] ?? [];
    }

}