<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/14 0014
 * Time: 11:19
 */

namespace driphp\tests;


use driphp\service\ElasticSearch;
use driphp\throws\ParameterInvalidException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

class ElasticSearchTest extends UniTest
{
    /**
     * @return ElasticSearch
     * @throws Missing404Exception
     */
    public function testIndex()
    {

        $elasticSearch = ElasticSearch::getInstance()->connect('http://127.0.0.1:9200');

        if ($elasticSearch->exist('a')) $elasticSearch->delete('a');
        if ($elasticSearch->exist('aa')) $elasticSearch->delete('aa');
        if ($elasticSearch->exist('index_found')) $elasticSearch->delete('index_found');

        $this->assertTrue($elasticSearch->exist('index_not_found') === false);

        $elasticSearch->create('index_found');
        $this->assertTrue($elasticSearch->exist('index_found') === true);
        $this->assertTrue($elasticSearch->delete('index_found') === true);

        return $elasticSearch;
    }

    /**
     * @depends testIndex
     * @param ElasticSearch $elasticSearch
     * @return ElasticSearch
     * @throws Missing404Exception
     * @throws NoNodesAvailableException
     * @throws ParameterInvalidException
     */
    public function testIndexAndGet(ElasticSearch $elasticSearch)
    {
        $index = $elasticSearch->index('a');
        $time = microtime(true);
        $index->set('bb', 'c', ['d' => $time]);
        $result = $index->get('bb', 'c');
        $this->assertTrue($time === $result->getSource()['d']);
        try {
            $index->get('bb', 'cc');
            $this->assertTrue(false);
        } catch (Missing404Exception $exception) {
            $this->assertTrue('{"_index":"a","_type":"bb","_id":"cc","found":false}' === $exception->getMessage());
        }

        $this->assertTrue(true);
        return $elasticSearch;
    }

    /**
     * @depends testIndexAndGet
     * @param ElasticSearch $elasticSearch
     * @return ElasticSearch
     * @throws NoNodesAvailableException
     * @throws \Elasticsearch\Common\Exceptions\BadRequest400Exception
     * @throws \driphp\throws\ParameterInvalidException
     */
    public function testSearchInUnicode(ElasticSearch $elasticSearch)
    {
        $index = $elasticSearch->index('aa');
        $index->set('bb', 'c1', [
            'city_name' => 'BeiJing',
        ]);
        $index->set('bb', 'c2', [
            'city_name' => 'DongJing',
        ]);
        $index->set('bb', 'c3', [
            'city_name' => 'NanJing',
        ]);
        $index->set('bb', 'c4', [
            'city_name' => 'LuoYang',
        ]);
        sleep(1); # 建立索引需要花费一些时间

        # 完整匹配
        $result = $index->search('bb', [
            'query' => [
                'match' => [
                    'city_name' => 'DongJing',
                ],
            ],
        ]);
        $this->assertTrue(1 === count($result));
        $this->assertTrue(isset($result['/aa/bb/c2']));
        $this->assertTrue('DongJing' === $result['/aa/bb/c2']->getSource()['city_name']);
        # 多余
        $result = $index->search('bb', [
            'query' => [
                'match' => [
                    'city_name' => 'DongJingDu',
                ],
            ],
        ]);
        $this->assertTrue(0 === count($result));
        # 子集
        $result = $index->search('bb', [
            'query' => [
                'match' => [
                    'city_name' => 'Jing',
                ],
            ],
        ]);
        $this->assertTrue(0 === count($result));


        $result = $index->search('', [
            'query' => [
                'match' => [
                    'city_name' => 'DongJing',
                ],
            ],
        ]);
        $this->assertTrue(1 === count($result));

        $result = $index->search();
        var_dump($result);
        $this->assertTrue(4 === count($result));#  获取全部

        return $elasticSearch;
    }

    /**
     * @depends testSearchInUnicode
     * @param ElasticSearch $elasticSearch
     * @return ElasticSearch
     */
    public function testStat(ElasticSearch $elasticSearch)
    {
        $res = $elasticSearch->stats();
        $this->assertTrue(count($res['indices']) >= 2);
        $this->assertTrue(isset($res['indices']['a']));
        $this->assertTrue(isset($res['indices']['aa']));
        return $elasticSearch;
    }

}