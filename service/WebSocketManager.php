<?php
/**
 * User: linzhv@qq.com
 * Date: 06/05/2018
 * Time: 17:01
 */
declare(strict_types=1);


namespace driphp\service;

use driphp\Component;
use driphp\core\Logger;
use driphp\service\websocket\AccountManager;
use driphp\throws\MethodNotFoundException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class WebSocketServer
 *
 * @method WebSocketManager  onStart(callable $callback)
 * @method WebSocketManager  onShutdown(callable $callback)
 * @method WebSocketManager  onWorkerStart(callable $callback)
 * @method WebSocketManager  onWorkerStop(callable $callback)
 * @method WebSocketManager  onWorkerExit(callable $callback)
 * @method WebSocketManager  onConnect(callable $callback)
 * @method WebSocketManager  onReceive(callable $callback)
 * @method WebSocketManager  onPacket(callable $callback)
 * @method WebSocketManager  onClose(callable $callback)
 * @method WebSocketManager  onBufferFull(callable $callback)
 * @method WebSocketManager  onBufferEmpty(callable $callback)
 * @method WebSocketManager  onTask(callable $callback)
 * @method WebSocketManager  onFinish(callable $callback)
 * @method WebSocketManager  onPipeMessage(callable $callback)
 * @method WebSocketManager  onWorkerError(callable $callback)
 * @method WebSocketManager  onManagerStart(callable $callback)
 * @method WebSocketManager  onManagerStop(callable $callback)
 *
 * @method WebSocketManager getInstance() static
 * @package driphp\service
 */
class WebSocketManager extends Component
{
    /**
     * @var Server websocket服务器
     */
    private $server = null;

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        if (!isset($this->server)) {
            $this->server = new Server($this->config['ip'], $this->config['port']);
        }
        return $this->server;
    }

    protected $config = [
        'ip' => '0.0.0.0',
        'port' => 20180,
        'debug' => true,
    ];
    /**
     * @var array 事件回调列表
     */
    private $events = [
        # Server
        'Start' => false,
        'Shutdown' => false,
        'WorkerStart' => false,
        'WorkerStop' => false,
        'WorkerExit' => false,
        'Connect' => false,
        'Receive' => false,
        'Packet' => false,
        'Close' => false,
        'BufferFull' => false,
        'BufferEmpty' => false,
        'Task' => false,
        'Finish' => false,
        'PipeMessage' => false,
        'WorkerError' => false,
        'ManagerStart' => false,
        'ManagerStop' => false,

        # WebSocket
        'HandShake' => false,
        'Open' => false,
        'Request' => false,
        'Message' => false,
    ];


    /**
     * WebSocket建立连接后进行握手
     * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
     * 内置的握手协议为: Sec-WebSocket-Version: 13
     * @param Request $request
     * @param Response $response
     * @return bool 必须返回true表示握手成功，返回其他值表示握手失败
     */
    public function onHandShake(Request $request, Response $response)
    {
        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'] ?? '';
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) or 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        $key = base64_encode(sha1(
            $secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
            true
        ));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];
        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
        return true;
    }

    /**
     * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数
     *
     * open事件中 可以调用push向客户端发送数据或者调用close关闭连接
     *
     * @param Server $server
     * @param Request $request Http请求对象，包含了客户端发来的握手请求信息
     * @return void
     */
    public function onOpen(Server $server, Request $request)
    {

    }

    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数
     *
     * data格式：
     * {
     *      'type':'register|exchange',
     *      'data':{},
     *      'sender': 'nancy@gmail.com',
     *      'receiver': 'justin',
     *      'token': '....',
     * }
     *
     * 返回值格式:
     * {
     *      'code'=>0, # 0表示成功，大于0表示对应的错误代码
     *      'msg'=>'', # 提示消息
     *      'data'=> JsonObject # 返回的数据部分
     * }
     *
     * @param Server $server
     * @param Frame $frame 包含了客户端发来的数据帧信息
     * @return mixed
     */
    public function onMessage(Server $server, Frame $frame)
    {
        $fd = (int)$frame->fd;
        $data = json_decode($frame->data, true, 10, JSON_BIGINT_AS_STRING);
        # 变量
        $sender = $data['sender'] ?? '';
        $type = $data['type'] ?? '';
        $token = $data['token'] ?? '';
        if (!$sender) return $this->respondError($fd, 104);
        if (!$type) return $this->respondError($fd, 105);
        if (!$token) return $this->respondError($fd, 106);

        if (!$data) {
            $this->respondError($fd, 1000);
        } else {
            switch ($frame->opcode) {
                case WEBSOCKET_OPCODE_TEXT: # $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
                    # 验证消息的真实性
                    if (!$this->accountManager->validate($sender, $token)) {
                        $this->respondError($fd, 1);
                    } else {
                        # 验证通过
                        switch ($type) {
                            case 'register': # 登记账号
                                if ($this->accountManager->getFdByName($sender)) {
                                    $this->accountManager->unregister($sender);
                                }
                                $this->accountManager->register($sender, $fd);
                                $this->respondSuccess($fd);
                                break;
                            case 'exchange': # 信息交换
                                $receiver = $data['receiver'] ?? '' or $this->respondError($fd, 1001);
                                if ($this->accountManager->getFdByName($sender)) {
                                    if ($targetFd = $this->accountManager->getFdByName($receiver)) {
                                        unset($data['token']);# 删除token
                                        $server->push($targetFd, json_encode($data));
                                        $this->respondSuccess($fd);
                                    } else {
                                        $this->respondError($fd, 2001);
                                    }
                                } else {
                                    # 未注册
                                    $this->respondError($fd, 3);
                                }

                                break;
                            default:
                                return $this->respondError($fd, 1002);
                        }
                    }
                    break;
                case WEBSOCKET_OPCODE_BINARY:
                    break;
            }
        }
        return true;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response)
    {
        // 接收http请求从get获取message参数的值，给用户推送
        // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->write(json_encode($request->get));
    }


    private $codeMap = [
        # 基本错误
        0 => 'success',
        1 => 'invalid access',
        2 => 'another register occupy',
        3 => 'not registered',
        # 1000～1999 参数错误
        1000 => 'wrong request data',
        1001 => 'receiver not set',
        1002 => 'invalid type',
        1004 => 'sender not set',
        1005 => 'type not set',
        1006 => 'token not set',
        # 2000~2999 逻辑错误
        2001 => 'receiver offline',
    ];

    /**
     * 回复正确的消息
     * @param int $fd
     * @param array $data
     * @param string|null $message
     * @return bool
     */
    public function respondSuccess(int $fd, array $data = [], string $message = null)
    {
        # 客户端存在，且状态为active
        if ($this->server->exist($fd)) {
            $data['time'] = time();
            $this->server->push($fd, json_encode([
                'code' => 0,
                'msg' => $message ?? $this->codeMap[0] ?? 'success',
                'data' => $data,
            ]));
        }
        return true;
    }

    /**
     * 响应错误的消息
     * @param int $fd
     * @param int $code
     * @param string|null $message
     * @return bool
     */
    public function respondError(int $fd, int $code, string $message = null)
    {
        # 客户端存在，且状态为active
        if ($this->server->exist($fd)) {
            $this->server->push($fd, json_encode([
                'code' => $code,
                'msg' => $message ?? $this->codeMap[$code] ?? 'error',
                'data' => ['time' => time()],
            ]));
        }
        return true;
    }


    /**
     * 回复并关闭连接
     * @param int $fd
     * @param int $code 响应代号
     * @param string|null $message
     * @return void
     */
    public function closeWith(int $fd, int $code = 0, string $message = null)
    {
        $this->respondError($fd, $code, $message);
        $this->getServer()->close($fd, false); # 参数二必须设置为false，否则错误回应消息可能来不及发出
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this|mixed
     * @throws MethodNotFoundException
     */
    public function __call(string $name, array $arguments)
    {
        if (strpos($name, 'on') === 0) {
            $event = substr($name, 2);
            if (isset($this->events[$event]) and is_callable($arguments[0])) {
                $this->events[$event] = $arguments[0];
                return $this;
            }
        }
        throw new MethodNotFoundException($name);
    }

    /** @var AccountManager $validator */
    private $accountManager;

    /**
     * 开启服务
     * @param AccountManager $accountManager
     * @return void
     */
    public function start(AccountManager $accountManager)
    {
        $this->accountManager = $accountManager;
        $server = $this->getServer();
        $this->events['Message'] = [$this, 'onMessage'];
        foreach ($this->events as $event => $callback) {
            if ($callback) $server->on($event, function (...$arguments) use ($callback) {
                try {
                    call_user_func_array($callback, $arguments);
                } catch (\Throwable $throwable) {
                    Logger::getInstance()->emergency($throwable->getMessage());
                }
            });
        }
        $server->start();
    }

}