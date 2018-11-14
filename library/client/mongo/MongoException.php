<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 11:14
 */

namespace driphp\library\client\mongo;


use driphp\KernelException;

class MongoException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 110003;
    }
}