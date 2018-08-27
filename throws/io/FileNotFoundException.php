<?php
/**
 * Created by PhpStorm.
 * User: zhonghuanglin
 * Date: 22/08/2018
 * Time: 15:37
 */

namespace driphp\throws\io;


use driphp\throws\IOException;

class FileNotFoundException extends IOException
{
    public function getExceptionCode(): int
    {
        return 10032;
    }
}