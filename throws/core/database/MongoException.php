<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 17:02
 */
declare(strict_types=1);


namespace sharin\throws\core\database;


use sharin\throws\core\DatabaseException;

/**
 * Class MongoException Mongo数据库异常
 * @package sharin\throws\core\database
 */
class MongoException extends DatabaseException
{
    public function __construct($message = '', int $code = -1)
    {
        parent::__construct($message, $code);
    }
}