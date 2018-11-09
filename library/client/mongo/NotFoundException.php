<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 11:21
 */

namespace driphp\library\client\mongo;


class NotFoundException extends MongoException
{
    public function __construct($message = '')
    {
        parent::__construct($message);
    }
}