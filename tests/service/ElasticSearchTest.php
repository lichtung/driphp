<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/14 0014
 * Time: 11:19
 */

namespace driphp\tests;


use driphp\service\elastic\Index;
use driphp\service\ElasticSearch;
use driphp\throws\service\elastic\ResourceAlreadyExistsSearchException;
use driphp\throws\service\ElasticSearchException;

class ElasticSearchTest extends UnitTest
{

    public function testIndex()
    {
        $elasticSearch = ElasticSearch::getInstance()->connect('http://127.0.0.1:9200');
        define('FIRST_KEY', 'hello_world');
        $this->assertTrue($elasticSearch->exist(FIRST_KEY) === false); # 首先是不存在
        try {
            $this->assertTrue($elasticSearch->create(FIRST_KEY)); # 创建成功
            $this->assertTrue($elasticSearch->create(FIRST_KEY)); # 创建失败: 已经存在
            $this->assertTrue(false);
        } catch (ResourceAlreadyExistsSearchException $exception) {
            $this->assertTrue(true);
        } catch (ElasticSearchException $exception) {
            $this->assertTrue(false);
        }
        $this->assertTrue($elasticSearch->exist(FIRST_KEY) === true); # 存在

        $this->assertTrue(isset($elasticSearch->getIndices()[FIRST_KEY])); # 存在

        $this->assertTrue(true === $elasticSearch->delete(FIRST_KEY)); # 删除成功
        $this->assertTrue(false === $elasticSearch->delete(md5(FIRST_KEY))); # 删除存在的

        $this->assertTrue($elasticSearch->exist(FIRST_KEY) === false); # 不存在

        $elasticSearch->delete(FIRST_KEY);
        return $elasticSearch;
    }

    /**
     * @depends testIndex
     * @param ElasticSearch $elasticSearch
     * @throws
     */
    public function testDocument(ElasticSearch $elasticSearch)
    {
        if (!$elasticSearch->exist('my_index')) $elasticSearch->create('my_index');
        $index = $elasticSearch->index('my_index');
        $this->assertTrue($index instanceof Index);
        $res = $index->set('fate', 'saber', [
            'url' => 'https://api.e.qq.com/v1.0/user_actions/add?access_token=65d4e94d575a2575105d64c6d1e3439a&timestamp=1531772857&nonce=c251a14559e4dd9b856e0807e34867d9',
            'content' => [
                'action_time' => 1531772857,
                'user_id' => [
                    'hash_idfa' => '1018f354d0e2b12dd260bdc1608ebb32',
                ],
                'action_type' => 'PURCHASE',
            ],
        ]);
        var_dump($res);
        $this->assertTrue(true);
    }

//    /**
//     * set -> get -> delete
//     * @depends testIndex
//     * @param ElasticSearch $elasticSearch
//     * @return ElasticSearch
//     * @throws Missing404Exception
//     * @throws NoNodesAvailableException
//     * @throws ParameterInvalidException
//     */
//    public function testDocument(ElasticSearch $elasticSearch)
//    {
//        define('NOW', microtime(true));
//        $index = $elasticSearch->index('my_index');
//
//        $index->set('my_type', 'c', ['d' => NOW, 'date' => '2018-05-31']);
//        $result = $index->get('my_type', 'c');
//        # 获取文档
//        $this->assertTrue(NOW === $result->getSource()['d']);
//        # 获取不存在的文档
//        try {
//            $index->get('my_type', 'cc');
//            $this->assertTrue(false);
//        } catch (Missing404Exception $exception) {
//            $this->assertTrue('{"_index":"my_index","_type":"my_type","_id":"cc","found":false}' === $exception->getMessage());
//        }
//
//        # 删除
//        $this->assertTrue($index->delete('my_type', 'c'));
//        try {
//            $index->get('my_type', 'c');
//            $this->assertTrue(false);
//        } catch (Missing404Exception $exception) {
//            $this->assertTrue('{"_index":"my_index","_type":"my_type","_id":"c","found":false}' === $exception->getMessage());
//        }
//        # 删除不存在的文档
//        try {
//            $index->delete('my_type', 'c.c.');
//            $this->assertTrue(false);
//        } catch (Missing404Exception $exception) {
//            echo $exception->getMessage();
//        }
//        return $elasticSearch;
//    }
//
//    /**
//     * @depends testDocument
//     * @param ElasticSearch $elasticSearch
//     * @return ElasticSearch
//     * @throws NoNodesAvailableException
//     * @throws \Elasticsearch\Common\Exceptions\BadRequest400Exception
//     * @throws \driphp\throws\ParameterInvalidException
//     */
//    public function testSearch(ElasticSearch $elasticSearch)
//    {
//        $index = $elasticSearch->index('my_index');
//
//        $index->set('my_type', 'c1', [
//            'city_name' => 'BeiJing', 'date' => '2018-05-31 13:59:45',
//        ]);
//        $index->set('my_type', 'c2', [
//            'city_name' => 'DongJing', 'date' => '2018-04-30 13:59:45',
//        ]);
//        $index->set('my_type', 'c3', [
//            'city_name' => 'NanJing', 'date' => '2018-06-30',
//        ]);
//        $index->set('my_type', 'c4', [
//            'city_name' => 'LuoYang', 'date' => '2018-05-30 13:59:44',
//        ]);
//        sleep(2); # 建立索引需要花费一些时间
//
//        # 完整匹配
//        $result = $index->match('city_name', 'DongJing', 'my_type');
//        $this->assertTrue(1 === count($result));
//        $this->assertTrue(isset($result['/my_index/my_type/c2']) and 'DongJing' === $result['/my_index/my_type/c2']->getSource()['city_name']);
//
//        # 多余
//        $this->assertTrue(0 === count($index->match('city_name', 'DongJingDu', 'my_type')));
//        # 名称的部分
//        $this->assertTrue(0 === count($index->match('city_name', 'Jing', 'my_type')));
//
//        $this->assertTrue(1 === count($index->match('city_name', 'LuoYang')));
//
//        $result = $index->search();
//        if (4 !== count($result)) dumpout($result);
//        $this->assertTrue(4 === count($result));#  获取全部
//
//
//        $result = $index->range(['date' => [
//            'gte' => '2018-05-01',
//            'lte' => '2018-05-31',
//        ]]);
//        $this->assertTrue(2 === count($result));
//        foreach ($result as $document) {
//            $this->assertTrue(in_array($document->getSource()['date'], ['2018-05-31 13:59:45', '2018-05-30 13:59:44']));
//        }
//
//        return $elasticSearch;
//    }

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