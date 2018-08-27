<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 12:05
 */

namespace driphp\throws\io;


use driphp\throws\IOException;

class FileWriteException extends IOException
{
    public function getExceptionCode(): int
    {
        return 10031;
    }
}