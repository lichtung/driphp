<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/7/17 0017
 * Time: 18:29
 */

namespace driphp\throws\service;

use driphp\DripException;

/**
 * Class ElasticSearchException ElasticSearch异常
 * @package driphp\throws\service
 */
class ElasticSearchException extends DripException
{
    protected $data = [];

    /**
     * ElasticSearchException constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        if ($data = json_decode((string)$message, true)) {
            $this->data = $data;
            $message = $data['error']['reason'] ?? '';
        }
        parent::__construct($message);
    }


    /**
     * @return int
     */
    public function getExceptionCode(): int
    {
        return 80000;
    }
}