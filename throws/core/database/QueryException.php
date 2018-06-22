<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:26
 */
declare(strict_types=1);


namespace driphp\throws\core\database;


use driphp\throws\core\DatabaseException;

/**
 * Class QueryException SQL查询异常
 * @package driphp\throws\core\database
 */
class QueryException extends DatabaseException
{

    public function getExceptionCode(): int
    {
        return 3004;
    }
}