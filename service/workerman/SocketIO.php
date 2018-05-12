<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 17:40
 */
declare(strict_types=1);


namespace sharin\service\workerman;

use sharin\service\workerman\socketio\Message;
use sharin\service\workerman\socketio\Passport;
use PHPSocketIO\SocketIO as SIO;
use sharin\service\workerman\socketio\_Socket;
use sharin\service\workerman\socketio\ConnectionHandlerInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class SocketIO
{
    /**
     * @var int
     */
    protected $port = null;
    protected $httpPort = null;
    /**
     * @var SIO
     */
    protected $socketio = null;
    /**
     * @var Worker
     */
    protected $httpIO = null;
    /**
     * @var ConnectionHandlerInterface
     */
    protected $handler = null;
    /**
     * @var _Socket[]
     */
    protected $socketPool = [];

    /**
     * SocketIO constructor.
     * @param int $port 套接字端口
     * @param int $httpPort http端口
     */
    public function __construct(int $port = 1992, int $httpPort = 1993)
    {
        $this->port = $port;
        $this->httpPort = $httpPort;
        $this->socketio = new SIO($port);
    }

    public function setHandler(ConnectionHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * 计算连接池中的注册连接数量
     * @return int
     */
    public function countSocketPool(): int
    {
        return count($this->socketPool);
    }

    /**
     * 返回连接
     * @param string $id
     * @return _Socket
     */
    public function getSocket(string $id)
    {
        return $this->socketPool[$id] ?? null;
    }

    /**
     * 向连接池中添加套接字
     * @param _Socket $socket
     * @return bool
     */
    public function addSocket($socket): bool
    {
        if ($socket->passport and $id = $socket->passport->id) {
            if (isset($this->socketPool[$id])) {
                $socket->error = 'socket id exist';
                return false;
            } else {
                $this->socketPool[$id] = $socket;
                return true;
            }
        } else {
            $socket->error = 'failed to add anonymous socket';
            return false;
        }
    }

    /**
     * 删除套接字连接
     * @param _Socket $socket
     * @return bool
     */
    public function removeSocket($socket): bool
    {
        if ($socket->passport and $id = $socket->passport->id) {
            if (isset($this->socketPool[$id])) {
                unset($this->socketPool[$id]);
                echo "client '{$id}' disconnect\n";
                return true;
            } else {
                $socket->error = 'socket id not exist';
                return false;
            }
        } else {
            $socket->error = 'failed to remove anonymous socket';
            return false;
        }
    }

    /**
     * 向套接字发送失败的消息
     * @param _Socket $socket
     * @param string $message 为空时自动从socket中获取错误信息
     * @return void
     */
    public static function emitError($socket, string $message = '')
    {
        $message or $message = $socket->error;
        try {
            $socket->emit('message', [
                'status' => 0,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
        }
        echo "F:{$message}\n";
    }

    /**
     * 向套接字发送成功的消息
     * @param _Socket $socket
     * @param string $message
     * @return void
     */
    public static function emitSuccess($socket, string $message = 'OK')
    {
        try {
            $socket->emit('message', [
                'status' => 1,
                'message' => $message,
            ]);
            echo "S:{$message}\n";
        } catch (\Exception $e) {
        }
    }

    /**
     * 开启进程
     * @return void
     */
    public function start()
    {
        $context = $this;
        # 监听套接字
        $this->socketio->on('connection', function (
            /** @var _Socket $socket */
            $socket) use (
            /** @var SocketIO $context */
            &$context,
            /** @var _Socket[] $socketPool */
            &$socketPool
        ) {
            //处理连接事件
            if ($context->handler->connect($socket, $context)) {
                self::emitSuccess($socket, 'access success');
                # 注册身份
                $socket->on('register', function ($info) use (
                    /** @var _Socket */
                    $socket,
                    /** @var SocketIO */
                    &$context,
                    /** @var _Socket[] */
                    &$socketPool
                ) {
                    if (is_array($info)) {
                        if ($socket->passport) {
                            self::emitError($socket, 'this socket has registered');
                        } else {
                            if (!empty($info['id'])) {
                                if ($this->handler->register(new Message($info), $socket, $context)) {
                                    if ($this->getSocket($info['id'])) {
                                        self::emitError($socket, 'register socket exist!');
                                    } else {
                                        # 创建和添加passport资料
                                        $passport = new Passport($info['id'], $info['pwd'] ?? '');
                                        $socket->passport = $passport;
                                        # 添加连接，失败时推送错误信息并且
                                        if ($this->addSocket($socket)) {
                                            self::emitSuccess($socket, "register {$info['id']} success");
                                        } else {
                                            self::emitError($socket);
                                        }
                                    }
                                } else {
                                    self::emitError($socket, 'register deny');
                                }
                            } else {
                                self::emitError($socket, 'register require id');
                            }
                        }
                    } else {
                        self::emitError($socket, 'please emit object ');
                    }
                });
                //客户端发送消息时执行
                $socket->on('message', function ($message) use ($socket, &$context) {
                    if (is_array($message)) {
                        if ($socket->passport) {
                            $context->handler->message(new Message($message), $socket, $context);
                            echo "receive message from unregistered client\n";
                        } else {
                            self::emitError($socket, 'deny message from unregistered client');
                        }
                    } else {
                        self::emitError($socket, 'please emit object ');
                    }
                });
                # 断开连接
                $socket->on('disconnect', function () use (
                    /** @var _Socket */
                    $socket,
                    /** @var SocketIO */
                    &$context,
                    /** @var _Socket[] */
                    &$socketPool
                ) {
                    if ($socket->passport) {
                        $context->handler->disconnect($socket, $context);
                        $context->removeSocket($socket);
                    } else {
                        unset($socket);
                        echo "an unregister client disconnect\n";
                    }
                });
            } else {
                # 客户端会在一段时间内自动发起重新连接的操作，这将导致更加浪费服务器资源，所以不进行unset($socket)的操作
                self::emitError($socket, 'access deny');
            }
        });
        # 允许web服务发送消息(允许后台或者前段发送一次性的消息)
        $this->socketio->on('workerStart', function () use ($context) {
            $context->httpIO = new Worker('http://127.0.0.1:' . $context->httpPort);
            /**
             * http服务监听到连接时回调用
             * @param TcpConnection $connection
             * @param array $data
             * @return void
             */
            $context->httpIO->onMessage = function (TcpConnection $connection, array $data) use ($context) {
                try {
                    if (true !== $context->handler->request($connection, $data, $context)) {
                        # 请求参数
                        $get = $data['get'] ?? [];
                        $post = $data['post'] ?? [];
                        $request = $post ? array_merge($get, $post) : $get;
                        # 请求功能
                        switch ($request['function'] ?? '') {
                            # 转发功能
                            case 'transmit':
                                if (!empty($request['to']) and !empty($request['from'])) {
                                    if ($socket = $context->getSocket($request['to'])) {
                                        $socket->emit('message', $request);
                                        $connection->send(json_encode([
                                            'status' => 1,
                                            'message' => "transmit finished",
                                        ]));
                                    } else {
                                        $connection->send(json_encode([
                                            'status' => 0,
                                            'message' => "transmit to '{$request['to']}'failed,target not exist",
                                        ]));
                                    }
                                } else {
                                    $connection->send(json_encode([
                                        'status' => 0,
                                        'message' => 'transmit require to,from ',
                                    ]));
                                }
                                break;
                        }
                    }
                    $connection->close();
                } catch (\Throwable $throwable) {
                    $error = $throwable->getMessage();
                    echo "Error:{$error}\n";
                }
            };
            // 执行监听
            $context->httpIO->listen();
        });
        # 开启套接字服务进程和http服务进程
        Worker::runAll();
    }
}