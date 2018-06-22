<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 17:03
 */
declare(strict_types=1);


namespace driphp\throws\core\database\mongo;


use driphp\throws\core\database\MongoException;

/**
 * Class PersistException Mongo保存异常
 * @package driphp\throws\core\database\mongo
 */
class PersistException extends MongoException
{

    public function getExceptionCode(): int
    {
        return 3602;
    }
}