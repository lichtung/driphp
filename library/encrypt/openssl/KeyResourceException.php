<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 16:01
 */

namespace driphp\library\encrypt\openssl;


class KeyResourceException extends OpenSSLException
{
    public function getExceptionCode(): int
    {
        return 100001;
    }

}