<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 12:37
 */
declare(strict_types=1);

namespace driphp\service;

use driphp\Component;
use driphp\Kernel;
use driphp\service\rabbit\OnReceiveInterface;
use driphp\DripException;
use driphp\throws\core\ClassNotFoundException;
use driphp\throws\service\FatalException;
use driphp\throws\service\RabbitMQException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class RabbitMQ
 *
 * @see http://www.rabbitmq.com/tutorials/tutorial-one-php.html
 *
 *
 *
 * @see http://www.rabbitmq.com/tutorials/tutorial-two-php.html
 * 多个worker一起工作的时候为了实现公平调度（Fair dispatch）
 * 只有处理完成并回复的worker才会收到下一条消息
 * You might have noticed that the dispatching still doesn't work exactly as we want. For example in a situation
 * with two workers, when all odd messages are heavy and even messages are light, one worker will be constantly
 * busy and the other one will do hardly any work. Well, RabbitMQ doesn't know anything about that and will still
 * dispatch messages evenly.
 *
 * This happens because RabbitMQ just dispatches a message when the message enters the queue. It doesn't look at
 * the number of unacknowledged messages for a consumer. It just blindly(盲目地) dispatches every n-th message to
 * the n-th consumer.
 *
 * In order to defeat that we can use the basic_qos method with the prefetch_count = 1 setting. This tells RabbitMQ
 * not to give more than one message to a worker at a time. Or, in other words, don't dispatch a new message to a
 * worker until it has processed and acknowledged the previous one. Instead, it will dispatch it to the next worker
 * that is not still busy.
 *
 * If all the workers are busy, your queue can fill up. You will want to keep an eye on that, and maybe add more
 * workers, or have some other strategy.
 * 如果所有的worker都非常繁忙，消息队列就可能会被填满
 * 因此需要添加监控，当消息队列即将被填满的时候能通知你增加更多的worker，或者其他策略。
 *
 *
 * @method RabbitMQ getInstance(string $index = '') static
 * @package driphp\service
 */
class RabbitMQ extends Component
{

    const EXCHANGE_DIRECT = 'direct';
    const EXCHANGE_TOPIC = 'topic';
    const EXCHANGE_HEADERS = 'headers';
    const EXCHANGE_FANOUT = 'fanout';

    protected $config = [
        'drivers' => [
            'default' => [
                'host' => '127.0.0.1',
                'port' => 5672,
                'user' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'context' => null,
                'keepalive' => false,
                'heartbeat' => 0,
                'basic_qos' => false,
                'prefetch_count' => 1, # 对于繁忙但是处理较快的队列来说，可以分配较大的数量
            ],
        ],
    ];

    /**
     * @var AMQPStreamConnection
     */
    private $connection;
    /**
     * @var AMQPChannel  Where most of the API for getting things done resides
     */
    private $channel = null;
    /**
     * @var string
     */
    private $queueName = '';
    /**
     * @var string
     */
    private $exchangeName = '';

    /**
     * RabbitMQ constructor.
     * @throws RabbitMQException It will be thrown if connect failed. Error message like "stream_socket_client(): unable to connect to tcp://111.0.0.0:5672 (A connection attempt failed because the connected party did not properly respond after a period of time, or established connection failed because connected host has failed to respond."
     */
    protected function initialize()
    {
        try {
            $parameters = [];
            $config = &$this->config['drivers'][$this->index];
            foreach (['host', 'port', 'user', 'password', 'vhost', 'insist', 'login_method', 'login_response', 'locale',
                         'connection_timeout', 'read_write_timeout', 'context', 'keepalive', 'heartbeat', 'basic_qos'] as $item) {
                $parameters[$item] = $config[$item];
            }
            $this->connection = Kernel::factory(AMQPStreamConnection::class, $parameters);
            $this->channel = $this->connection->channel();
            $this->channel->basic_qos(null, $config['prefetch_count'] ?? 1, null);
        } catch (ClassNotFoundException $exception) {
            DripException::dispose($exception);
        } catch (\Throwable $throwable) {
            throw new RabbitMQException($throwable->getMessage());
        }
    }

    /**
     * 声明队列
     * 注：
     *  如果一个队列已经被声明为持久/非持久，那么声明为非持久/持久就会报错
     *  持久化的作用是：RabbitMQ停止运行和崩溃时会丢失消息队列 (生产者和消费者需要同时为true或者false)
     * @param string $queueName
     * @param string $exchangeName
     * @param string $exchangeType
     * @param bool $durable 默认为false时队列不是持久的， queue won't be lost even if RabbitMQ restarts
     * @return RabbitMQ
     */
    public function queue(string $queueName = 'default', string $exchangeName = '', string $exchangeType = self::EXCHANGE_FANOUT, bool $durable = false): RabbitMQ
    {
        $this->channel->exchange_declare($this->exchangeName = $exchangeName, $exchangeType);
        #  declare the queue from which we're going to consume
        $this->channel->queue_declare($this->queueName = $queueName, false, $this->durable = $durable, false, false);
        return $this;
    }


    private
        $durable = false;

    /**
     * @param string $body
     * @param array $properties
     * @return void
     */
    public function send(string $body, array $properties = [])
    {
        if ($this->durable) {
            # 持久模式时消息也需要同样的设置
            $properties['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;
        }
        $msg = new AMQPMessage($body, $properties);
        $this->channel->basic_publish($msg, $this->exchangeName, $this->queueName);# 参数三指的是routing_key,即队列名称
    }

    /**
     * Our code will block while our $channel has callbacks. Whenever we receive a message our $callback function will be passed the received message.
     * @param OnReceiveInterface $handler
     * @return void
     * @throws DripException
     */
    public function receive(OnReceiveInterface $handler)
    {
        if (!DRI_IS_CLI) throw new DriException('cli-mode is required');
        $this->channel->basic_consume($this->queueName, '', false, !$handler->acknowledge(), false, false,
            function (AMQPMessage $message) use ($handler) {
                try {
                    $handler->message($message, $this);
                    if ($handler->acknowledge()) {
                        /** @var AMQPChannel $channel */
                        $channel = $message->delivery_info['channel'];
                        $channel->basic_ack($message->delivery_info['delivery_tag']);
                    }
                } catch (\Throwable $throwable) {
                    # no ack
                    try {
                        $handler->error($message, $this);
                    } catch (\Throwable $t) {
                        if ($t instanceof FatalException) {
                            throw $t;
                        } else {
                            DripException::dispose($t);
                        }
                    }
                }
            });

        # Our code will block while our $channel has callbacks.
        # Whenever we receive a message our $callback function will be passed the received message.
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel and $this->channel->close();
        $this->connection and $this->connection->close();
    }
}