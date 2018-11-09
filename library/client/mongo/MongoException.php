<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 11:14
 */

namespace driphp\library\client\mongo;


use driphp\DripException;

class MongoException extends DripException
{
    public function getExceptionCode(): int
    {
        return 110003;
    }
}