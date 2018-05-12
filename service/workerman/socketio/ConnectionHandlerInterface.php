<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 17:40
 */

namespace sharin\service\workerman\socketio;

use PHPSocketIO\Socket;
use sharin\service\workerman\SocketIO;
use Workerman\Connection\TcpConnection;

interface ConnectionHandlerInterface
{
    /**
     * 客户端开始连接时执行,控制客户端的连接（如连接数超过了2W，自动拒绝多余的连接）
     * 注：当返回false时，客户端的链接将保持死的连接状态，直到客户端主动断开连接
     * @param Socket $clientServer 连接的代表客户端的套接字
     * @param SocketIO $clientServerServer 服务端套接字
     * @return bool 为true时表示允许连接
     */
    public function connect(Socket $clientServer, SocketIO $clientServerServer): bool;
    /**
     * 客户端注册身份信息时
     * 注：只有通过了注册的客户端才能进行message通信操作
     * @param Message $message 注册身份时添加的连接
     * @param Socket $clientServer
     * @param SocketIO $clientServerServer
     * @return bool 返回true表示允许注册
     */
    public function register(Message $message, Socket $clientServer, SocketIO $clientServerServer): bool;
    /**
     * 处理客户端发送的消息
     * 注：如果客户端未注册，则自动返回未注册的提示信息
     * @param Message $message 消息
     * @param Socket $clientServer
     * @param SocketIO $clientServerServer
     * @return void 返回值对处理结果无影响
     */
    public function message(Message $message, Socket $clientServer, SocketIO $clientServerServer);
    /**
     * 客户端断开连接时执行
     * 注：执行完该函数之后，客户端将从连接池中回收
     * @param Socket $clientServer
     * @param SocketIO $clientServerServer
     * @return void 返回值对处理结果无影响
     */
    public function disconnect(Socket $clientServer, SocketIO $clientServerServer);
    /**
     * 当接收到tcp请求时进行回调
     * @param TcpConnection $connection 代表http请求的tcp连接
     * @param array $env 环境变量，包括 get，post，cookie，server，files
     * @param SocketIO $context 上下文环境
     * @return bool 是否处理完成，返回true时间阻止内部的系统逻辑
     */
    public function request(TcpConnection $connection, array $env, SocketIO $context);
}