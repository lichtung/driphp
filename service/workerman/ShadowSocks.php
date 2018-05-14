<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 17:36
 */
declare(strict_types=1);

namespace {

// 状态相关
    const STAGE_INIT = 0;
    const STAGE_ADDR = 1;
    const STAGE_UDP_ASSOC = 2;
    const STAGE_DNS = 3;
    const STAGE_CONNECTING = 4;
    const STAGE_STREAM = 5;
    const STAGE_DESTROYED = -1;
// 命令
    const CMD_CONNECT = 1;
    const CMD_BIND = 2;
    const CMD_UDP_ASSOCIATE = 3;
// 请求地址类型
    const ADDRTYPE_IPV4 = 1;
    const ADDRTYPE_IPV6 = 4;
    const ADDRTYPE_HOST = 3;
}

namespace sharin\service\workerman {

    use sharin\library\Encryptor;
    use Workerman\Connection\AsyncTcpConnection;
    use Workerman\Connection\TcpConnection;
    use Workerman\Worker;

    class ShadowSocks
    {

        /**
         * 解析shadowsocks客户端发来的socket5头部数据
         * @param string $buffer
         * @return array|bool
         */
        private static function parseSocket5Header(string $buffer): array
        {
            $addr_type = ord($buffer[0]);
            switch ($addr_type) {
                case ADDRTYPE_IPV4:
                    # 目标地址
                    $dest_addr = ord($buffer[1]) . '.' . ord($buffer[2]) . '.' . ord($buffer[3]) . '.' . ord($buffer[4]);

                    $port_data = unpack('n', substr($buffer, 5, 2));
                    $dest_port = $port_data[1];
                    $header_length = 7;
                    break;
                case ADDRTYPE_HOST:
                    $addrlen = ord($buffer[1]);
                    $dest_addr = substr($buffer, 2, $addrlen);
                    $port_data = unpack('n', substr($buffer, 2 + $addrlen, 2));
                    $dest_port = $port_data[1];
                    $header_length = $addrlen + 4;
                    break;
                case ADDRTYPE_IPV6:
                    echo "todo ipv6 not support yet\n";
                    return false;
                default:
                    echo "unsupported addrtype $addr_type\n";
                    return false;
            }
            return array($addr_type, $dest_addr, $dest_port, $header_length);
        }

        /**
         * 当shadowsocks客户端发来消息时
         * @param TcpConnection $connection
         * @param string $buffer
         * @return void
         */
        public static function onMessage(TcpConnection $connection, string $buffer)
        {
            try {// 判断当前连接的状态
                switch ($connection->stage) {
                    // 如果不是STAGE_STREAM，则尝试解析实际的请求地址及端口
                    case STAGE_INIT:
                    case STAGE_ADDR:
                        // 先解密数据
                        $buffer = $connection->encryptor->decrypt($buffer);
                        // 解析socket5头
                        $header_data = self::parseSocket5Header($buffer);
                        // 头部长度
                        $header_len = $header_data[3];
                        // 解析头部出错，则关闭连接
                        if (!$header_data) {
                            $connection->close();
                            return;
                        }
                        // 解析得到实际请求地址及端口
                        $host = $header_data[1];
                        $port = $header_data[2];
                        $address = "tcp://$host:$port";
                        // 异步建立与实际服务器的远程连接
                        /** @var AsyncTcpConnection $remote_connection */
                        $remote_connection = new AsyncTcpConnection($address);
                        $connection->opposite = $remote_connection;
                        $remote_connection->opposite = $connection;
                        // 流量控制，远程连接的发送缓冲区满，则停止读取shadowsocks客户端发来的数据
                        // 避免由于读取速度大于发送速导致发送缓冲区爆掉
                        $remote_connection->onBufferFull = function ($remote_connection) {
                            $remote_connection->opposite->pauseRecv();
                        };
                        // 流量控制，远程连接的发送缓冲区发送完毕后，则恢复读取shadowsocks客户端发来的数据
                        $remote_connection->onBufferDrain = function ($remote_connection) {
                            $remote_connection->opposite->resumeRecv();
                        };
                        // 远程连接发来消息时，进行加密，转发给shadowsocks客户端，shadowsocks客户端会解密转发给浏览器
                        $remote_connection->onMessage = function ($remote_connection, $buffer) {
                            $remote_connection->opposite->send($remote_connection->opposite->encryptor->encrypt($buffer));
                        };
                        // 远程连接断开时，则断开shadowsocks客户端的连接
                        $remote_connection->onClose = function ($remote_connection) {
                            // 关闭对端
                            $remote_connection->opposite->close();
                            $remote_connection->opposite = null;
                        };
                        /**
                         * 远程连接发生错误时（一般是建立连接失败错误），关闭shadowsocks客户端的连接
                         * @param object $remote_connection
                         * @param $code
                         * @param $msg
                         * @return void
                         */
                        $remote_connection->onError = function ($remote_connection, $code, $msg) use ($address) {
                            echo "remote_connection $address error code:$code msg:$msg\n";
                            $remote_connection->close();
                            if (!empty($remote_connection->opposite)) {
                                $remote_connection->opposite->close();
                            }
                        };
                        // 流量控制，shadowsocks客户端的连接发送缓冲区满时，则停止读取远程服务端的数据
                        // 避免由于读取速度大于发送速导致发送缓冲区爆掉
                        $connection->onBufferFull = function ($connection) {
                            $connection->opposite->pauseRecv();
                        };
                        // 流量控制，当shadowsocks客户端的连接发送缓冲区发送完毕后，继续读取远程服务端的数据
                        $connection->onBufferDrain = function ($connection) {
                            $connection->opposite->resumeRecv();
                        };
                        // 当shadowsocks客户端发来数据时，解密数据，并发给远程服务端
                        $connection->onMessage = function ($connection, $data) {
                            $connection->opposite->send($connection->encryptor->decrypt($data));
                        };
                        // 当shadowsocks客户端关闭连接时，关闭远程服务端的连接
                        $connection->onClose = function ($connection) {
                            $connection->opposite->close();
                            $connection->opposite = null;
                        };
                        /**
                         * 当shadowsocks客户端连接上有错误时，关闭远程服务端连接
                         * @param TcpConnection $connection
                         * @param $code
                         * @param $msg
                         * @return void
                         */
                        $connection->onError = function ($connection, $code, $msg) {
                            echo "connection err code:$code msg:$msg\n";
                            $connection->close();
                            if (isset($connection->opposite)) {
                                $connection->opposite->close();
                            }
                        };
                        // 执行远程连接
                        $remote_connection->connect();
                        // 改变当前连接的状态为STAGE_STREAM，即开始转发数据流
                        $connection->state = STAGE_STREAM;
                        // shadowsocks客户端第一次发来的数据超过头部，则要把头部后面的数据发给远程服务端
                        if (strlen($buffer) > $header_len) {
                            $remote_connection->send(substr($buffer, $header_len));
                        }
                }
            } catch (\Exception $e) {
            }
        }

        public static function register(int $port, string $password = '', string $method = '')
        {
            $password or $password = 'naz';
            $method or $method = 'aes-256-cfb';
            // 初始化worker，监听$PORT端口
            $worker = new Worker('tcp://0.0.0.0:' . $port);
            // 进程数量
            $worker->count = 12;
            // 名称
            $worker->name = 'ShadowWorker' . $port;
            // 当shadowsocks客户端连上来时
            $worker->onConnect = function ($connection) use ($method, $port, $password) {
                // 设置当前连接的状态为STAGE_INIT，初始状态
                $connection->stage = STAGE_INIT;
                // 初始化加密类
                $connection->encryptor = new Encryptor($password, $method);
            };

            $worker->onMessage = [self::class, 'onMessage'];

        }

        /**
         * 运行所有服务
         * @return void
         */
        public static function start()
        {
            Worker::runAll();
        }

    }
}

