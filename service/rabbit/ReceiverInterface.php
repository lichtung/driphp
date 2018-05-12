<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 15:46
 */
declare(strict_types=1);


namespace sharin\service\rabbit;


use PhpAmqpLib\Message\AMQPMessage;

interface ReceiverInterface
{

    public function handle(AMQPMessage $message): void;

}