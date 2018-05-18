<?php
/**
 * Created by linzh.
 * Github: git@github.com:lichtung/naz.git
 * Email: 784855684@qq.com
 * Datetime: 2017-02-07 13:35
 */
declare(strict_types = 1);

namespace lite\doc\ide;


class TcpConnection extends \Workerman\Connection\TcpConnection
{

    public $stage;

    public $state;
    /**
     * @var Encryptor
     */
    public $encryptor;
    /**
     * @var TcpConnection
     */
    public $opposite;

}