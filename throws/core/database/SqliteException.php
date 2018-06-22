<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 16:42
 */
declare(strict_types=1);


namespace driphp\throws\core\database;


use driphp\throws\core\DatabaseException;

/**
 * Class SqliteException SQLite异常
 * @package driphp\throws\core\database
 */
class SqliteException extends DatabaseException
{
    public function getExceptionCode(): int
    {
        return 3007;
    }
}