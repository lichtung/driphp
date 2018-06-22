<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:19
 */
declare(strict_types=1);


namespace driphp\throws\core;


use driphp\core\Logger;
use driphp\DripException;

/**
 * Class DatabaseException 数据库异常
 * @package driphp\throws\core
 */
abstract class DatabaseException extends DripException
{
    public function __construct($message)
    {
//        if (is_object($message)) {
//            if ($message instanceof \PDO or $message instanceof \PDOStatement) {
//                $info = $message->errorInfo();
//            } elseif ($message instanceof \PDOException) {
//                $info = $message->errorInfo;
//            }
//            $message = $info[2] ?? '';
//            $code = $info[1] ?? -1;
//        }
//        Logger::getInstance('database')->emergency($message);
        parent::__construct($message);
    }

    public function getExceptionCode(): int
    {
        return 20200;
    }
}