<?php
/**
 * User: linzhv@qq.com
 * Date: 05/05/2018
 * Time: 14:16
 */
declare(strict_types=1);

namespace {


    use driphp\service\RabbitMQ;

    require __DIR__ . '/../../boot.php';


    RabbitMQ::getInstance()->queue('asder', true)->send($argv[1] ?? 'hello world');
}
