<?php
/**
 * User: linzhv@qq.com
 * Date: 05/05/2018
 * Time: 12:52
 */
declare(strict_types=1);


namespace driphp\service\rabbit;

use driphp\service\RabbitMQ;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface OnReceiveInterface 接收到消息时的处理器
 * @package driphp\service\rabbit
 */
interface OnReceiveInterface
{
    /**
     * 返回消息是否进行答复
     * @return bool
     */
    public function acknowledge(): bool;

    /**
     * 处理消息
     * @param AMQPMessage $message 消息
     * @param RabbitMQ $context 上下文环境
     * @return void
     */
    public function message(AMQPMessage $message, RabbitMQ $context): void;

    /**
     * 处理错误信息
     * @param AMQPMessage $message 消息
     * @param RabbitMQ $context 上下环境
     * @return void
     */
    public function error(AMQPMessage $message, RabbitMQ $context): void;

}