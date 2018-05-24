<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/21 0021
 * Time: 16:23
 */

namespace {

    use Elasticsearch\ClientBuilder;

    require __DIR__ . '/../boot.php';
    require_once __DIR__ . '/../../vendor/autoload.php';

    $client = ClientBuilder::create()
        ->setHosts(['http://elastic:123456@10.7.65.49:9200'])
        ->build();

    $response = $client->search([
        'index' => 'callback-recharge',
        'type' => 'recharge',
        'body' => [
            'query' => [
                'match' => [
//                    'request' => 'https://api2.appsflyer.com/inappevent/id1370242412',
                    'request' => 'https://api2.appsflyer.com/inappevent/com.gtarcade.lodmena',
                ]
            ],
            'sort' => [
//                ['time' => ['order' => 'desc']],
            ],
        ]
    ]);
    var_dump($response);
}