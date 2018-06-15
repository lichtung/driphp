<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/24 0024
 * Time: 11:23
 */

namespace {

    use driphp\service\monitor\IOStatistics;

    require __DIR__ . '/../../boot.php';


    IOStatistics::getInstance()->run();
}