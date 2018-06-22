<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:19
 */
declare(strict_types=1);


namespace driphp\throws\core\database;


use driphp\throws\core\DatabaseException;

/**
 * Class ConnectException 数据库连接异常
 * @package driphp\throws\core\database
 */
class ConnectException extends DatabaseException
{

    public function getExceptionCode(): int
    {
        return 3001;
    }
}