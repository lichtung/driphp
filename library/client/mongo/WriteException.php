<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 10:57
 */

namespace driphp\library\client\mongo;


use driphp\KernelException;

class WriteException extends KernelException
{
    public function getExceptionCode(): int
    {
        return 110002;
    }
}