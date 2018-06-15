<?php
/**
 * User: linzhv@qq.com
 * Date: 05/05/2018
 * Time: 14:12
 */
declare(strict_types=1);

namespace {


    use PhpAmqpLib\Message\AMQPMessage;
    use driphp\service\rabbit\OnReceiveInterface;
    use driphp\service\RabbitMQ;
    use driphp\DriException;

    require __DIR__ . '/../../boot.php';

    class TestConsumer implements OnReceiveInterface
    {
        public function acknowledge(): bool
        {
            return true;
        }

        public function message(AMQPMessage $message, RabbitMQ $context): void
        {
            echo $message->body . PHP_EOL;
        }

        public function error(AMQPMessage $message, RabbitMQ $context): void
        {
            echo $message->body . PHP_EOL;
        }
    }

    try {
        RabbitMQ::getInstance()->queue('asder', true)->receive(new TestConsumer());
    } catch (DriException $e) {
        echo $e->getMessage();
    }
}
