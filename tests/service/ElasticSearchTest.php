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

        # 清理
        if ($elasticSearch->exist('my_index')) $elasticSearch->delete('my_index');
        if ($elasticSearch->exist('index_found')) $elasticSearch->delete('index_found');

        $this->assertTrue($elasticSearch->exist('index_not_found') === false);

        $this->assertTrue($elasticSearch->create('index_found') === true);
        $this->assertTrue(isset($elasticSearch->getIndices()['index_found']));
        $this->assertTrue($elasticSearch->exist('index_found') === true);
        $this->assertTrue($elasticSearch->delete('index_found') === true);
        $this->assertTrue($elasticSearch->exist('index_found') === false);

        $elasticSearch->create('my_index', [], [
            'my_type' => [
                'properties' => [
                    'date' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
                    ],
                ],
            ],
        ]);

        return $elasticSearch;
    }

    /**
     * set -> get -> delete
     * @depends testIndex
     * @param ElasticSearch $elasticSearch
     * @return ElasticSearch
     * @throws Missing404Exception
     * @throws NoNodesAvailableException
     * @throws ParameterInvalidException
     */
    public function testDocument(ElasticSearch $elasticSearch)
    {
        define('NOW', microtime(true));
        $index = $elasticSearch->index('my_index');

        $index->set('my_type', 'c', ['d' => NOW, 'date' => '2018-05-31']);
        $result = $index->get('my_type', 'c');
        # 获取文档
        $this->assertTrue(NOW === $result->getSource()['d']);
        # 获取不存在的文档
        try {
            $index->get('my_type', 'cc');
            $this->assertTrue(false);
        } catch (Missing404Exception $exception) {
            $this->assertTrue('{"_index":"my_index","_type":"my_type","_id":"cc","found":false}' === $exception->getMessage());
        }

        # 删除
        $this->assertTrue($index->delete('my_type', 'c'));
        try {
            $index->get('my_type', 'c');
            $this->assertTrue(false);
        } catch (Missing404Exception $exception) {
            $this->assertTrue('{"_index":"my_index","_type":"my_type","_id":"c","found":false}' === $exception->getMessage());
        }
        # 删除不存在的文档
        try {
            $index->delete('my_type', 'c.c.');
            $this->assertTrue(false);
        } catch (Missing404Exception $exception) {
            echo $exception->getMessage();
        }
        return $elasticSearch;
    }

    /**
     * @depends testDocument
     * @param ElasticSearch $elasticSearch
     * @return ElasticSearch
     * @throws NoNodesAvailableException
     * @throws \Elasticsearch\Common\Exceptions\BadRequest400Exception
     * @throws \driphp\throws\ParameterInvalidException
     */
    public function testSearch(ElasticSearch $elasticSearch)
    {
        $index = $elasticSearch->index('my_index');

        $index->set('my_type', 'c1', [
            'city_name' => 'BeiJing', 'date' => '2018-05-31 13:59:45',
        ]);
        $index->set('my_type', 'c2', [
            'city_name' => 'DongJing', 'date' => '2018-04-30 13:59:45',
        ]);
        $index->set('my_type', 'c3', [
            'city_name' => 'NanJing', 'date' => '2018-06-30',
        ]);
        $index->set('my_type', 'c4', [
            'city_name' => 'LuoYang', 'date' => '2018-05-30 13:59:44',
        ]);
        sleep(2); # 建立索引需要花费一些时间

        # 完整匹配
        $result = $index->match('city_name', 'DongJing', 'my_type');
        $this->assertTrue(1 === count($result));
        $this->assertTrue(isset($result['/my_index/my_type/c2']) and 'DongJing' === $result['/my_index/my_type/c2']->getSource()['city_name']);

        # 多余
        $this->assertTrue(0 === count($index->match('city_name', 'DongJingDu', 'my_type')));
        # 名称的部分
        $this->assertTrue(0 === count($index->match('city_name', 'Jing', 'my_type')));

        $this->assertTrue(1 === count($index->match('city_name', 'LuoYang')));

        $result = $index->search();
        if (4 !== count($result)) dumpout($result);
        $this->assertTrue(4 === count($result));#  获取全部


        $result = $index->range(['date' => [
            'gte' => '2018-05-01',
            'lte' => '2018-05-31',
        ]]);
        $this->assertTrue(2 === count($result));
        foreach ($result as $document) {
            $this->assertTrue(in_array($document->getSource()['date'], ['2018-05-31 13:59:45', '2018-05-30 13:59:44']));
        }

        return $elasticSearch;
    }

//    /**
//     * @depends testStat
//     * @param ElasticSearch $elasticSearch
//     * @throws Missing404Exception
//     * @throws NoNodesAvailableException
//     * @throws ParameterInvalidException
//     * @throws \Elasticsearch\Common\Exceptions\BadRequest400Exception
//     */
//    public function testMapping(ElasticSearch $elasticSearch)
//    {
//        if ($elasticSearch->exist('date_index')) {
//            $elasticSearch->delete('date_index');
//        }
//        $elasticSearch->create('date_index', [], [
//            'date_type' => [
//                'properties' => [
//                    'date' => [
//                        'type' => 'date',
//                    ],
//                ],
//            ],
//        ]);
//        $index = $elasticSearch->index('date_index');
//        $index->set('date_type', null, [
//            'a' => 1,
//        ]);
//        sleep(1);
//        $all = $index->search();
//        var_dump($all);
//        $this->assertTrue($elasticSearch->delete('date_index'));
//        $this->assertTrue(true);
//    }

}