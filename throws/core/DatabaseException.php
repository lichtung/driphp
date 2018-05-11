<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:19
 */
declare(strict_types=1);


namespace sharin\throws\core;


use sharin\core\Logger;
use sharin\SharinException;

abstract class DatabaseException extends SharinException
{
    public function __construct($message, int $code = -1)
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
        parent::__construct($message, $code);
    }
}