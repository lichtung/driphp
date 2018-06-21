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
use Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector;
use Elasticsearch\Serializers\SmartSerializer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

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
        'log_name' => '',
        'log_path' => '',
        'log_level' => Logger::INFO,
    ];
    /**
     * @var Client
     */
    private $client;
    /** @var Logger */
    private $logger;

    public function getClient()
    {
        return $this->client;
    }

    /**
     *
     * [
     *  // This is effectively equal to: "https://username:password@foo.com:9200/"
     *  [
     *      'host' => 'foo.com',
     *      'port' => '9200',
     *      'scheme' => 'https',
     *      'user' => 'username',
     *      'pass' => 'password'
     *  ],
     * // This is equal to "http://localhost:9200/"
     * [
     *      'host' => 'localhost',    // Only host is required
     * ]
     *
     *  '192.168.1.1:9200',         // IP + Port
     *  '192.168.1.2',              // Just IP
     *  'example.com:9201',         // Domain + Port
     *  'example.com',              // Just Domain
     *  'https://localhost',        // SSL to localhost
     *  'https://192.168.1.3:9200'  // SSL to IP + Port
     * ]
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_authorization_and_encryption
     * @param string ...$hosts
     * @return $this
     * @throws
     */
    public function connect(string... $hosts)
    {
        $this->client = ClientBuilder::create()->setHosts($hosts);
        if ($this->config['retries']) {
            $this->client->setRetries((int)$this->config['retries']);
        }
        if ($this->config['log_name']) {
            $this->logger = new Logger($this->config['log_name']);
            $this->logger->pushHandler(new StreamHandler($this->config['log_path'], $this->config['log_level']));
            $this->client->setLogger($this->logger);
        }
        $this->client->setSerializer(SmartSerializer::class)
            ->setSelector(StickyRoundRobinSelector::class);
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
     * 创建索引
     * @param string $index
     * @param array $settings
     * @param array $mappings
     * @return bool
     */
    public function create(string $index, array $settings = [], array $mappings = []): bool
    {
        $body = [];
        $settings and $body['settings'] = $settings;
        $mappings and $body['mappings'] = $mappings;
        $res = $this->client->indices()->create([
                'index' => $index,
                'body' => $body,
            ])['acknowledged'] ?? 0;
        return $res > 0;
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