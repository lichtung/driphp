<?php
/**
 * Created by PhpStorm.
 * User: v.linzh
 * Date: 2018/10/17
 * Time: 11:39
 *
 * 181017 UZ 海外日志服连接国内数据库失败,导致数据回传
 */

namespace {
    $config = [
        'name' => 'ad_basis',
        'user' => 'ad_user',
        'passwd' => 'SMptoXq47MwTRzHSD4kU',
        'host' => '10.7.26.19',
        'port' => 3306,
        'charset' => 'UTF8',
        'dsn' => null,
    ];
    function buildDSN(array $config): string
    {
        $dsn = "mysql:host={$config['host']}";
        empty($config['name']) or $dsn .= ";dbname={$config['name']}";
        empty($config['port']) or $dsn .= ";port={$config['port']}";
        empty($config['socket']) or $dsn .= ";unix_socket={$config['socket']}";
        empty($config['charset']) or $dsn .= ";charset={$config['charset']}";//$this->options[\PDO::MYSQL_ATTR_INIT_COMMAND]    =   'SET NAMES '.$config['charset'];
        return $dsn;
    }

    $pdo = new PDO(buildDSN($config), $config['user'], $config['passwd']);

}