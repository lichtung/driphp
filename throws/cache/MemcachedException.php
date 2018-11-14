<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 10:32
 */

namespace driphp\throws\cache;


use driphp\KernelException;

class MemcachedException extends KernelException
{

    public function __construct($message, $code = 0)
    {
        parent::__construct("[{$code}]:{$message}");
    }

    public function getExceptionCode(): int
    {
        return 1;
    }
}