<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 17:02
 */
declare(strict_types=1);


namespace driphp\throws\core\database\mongo;


use driphp\throws\core\database\MongoException;

/**
 * Class DatabaseNotSelectException MongoDB数据库未选择
 * @package driphp\throws\core\database\mongo
 */
class DatabaseNotSelectException extends MongoException
{

    public function getExceptionCode(): int
    {
        return 3601;
    }
}