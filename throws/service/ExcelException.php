<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/10 0010
 * Time: 20:16
 */

namespace driphp\throws\service;


use driphp\DriException;

/**
 * Class ExcelException Excel异常
 * @package driphp\throws\service
 */
class ExcelException extends DriException
{

    public function getExceptionCode(): int
    {
        return 20100;
    }
}