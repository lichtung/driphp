<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/14 0014
 * Time: 11:19
 */

namespace driphp\tests;


use driphp\service\ElasticSearch;

class ElasticSearchTest extends UniTest
{
    public function testIndex()
    {
        $instance = ElasticSearch::getInstance()->connect('http://127.0.0.1:9200');
        $instance->index('a', 'b', 'c', ['d' => 'd']);
        dumpout($instance->stats());
        $this->assertTrue(true);
    }

}