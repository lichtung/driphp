<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/4 0004
 * Time: 14:59
 */

$datetime = new \DateTime(date('Y-m-d H:i:s', 1000000000), new \DateTimeZone('UTC'));
var_dump($datetime->format('Y-m-d H:i:s.000'));
