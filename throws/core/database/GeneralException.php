<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 23:27
 */
declare(strict_types=1);


namespace driphp\throws\core\database;


use driphp\throws\core\DatabaseException;

/**
 * Class GeneralException 
 * @package driphp\throws\core\database
 */
class GeneralException extends DatabaseException
{

    public function getExceptionCode(): int
    {
        return 3003;
    }
}