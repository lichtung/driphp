<?php
/**
 * User: linzhv@qq.com
 * Date: 19/03/2018
 * Time: 13:46
 */
declare(strict_types=1);


namespace driphp\service;


use driphp\Component;
use Swoole\Server;

/**
 * Class Swoole Swoole助手类
 *
 * @method  Swoole getInstance() static
 *
 *
 * @property int $availableDrivers
 *
 *
 * @property int $max_conn       最大连接    最大允许维持多少个tcp连接,超过此数量后，新进入的连接将被拒绝
 * @property int $daemonize      守护进程化    加入此参数后，执行php server.php将转入后台作为守护进程运行
 * @property int $reactor_num    reactor线程数    通过此参数来调节poll线程的数量，以充分利用多核, 默认设置为CPU核数
 * @property int $worker_num     worker进程数     PHP代码中是全异步非阻塞，worker_num配置为CPU核数的1-4倍即可。如果是同步阻塞，worker_num配置为100或者更高，具体要看每次请求处理的耗时和操作系统负载状况。
 * @property int $backlog        Listen队列长度    backlog => 128，此参数将决定最多同时有多少个待accept的连接，swoole本身accept效率是很高的，基本上不会出现大量排队情况。
 * @property int $max_request    此参数表示worker进程在处理完n次请求后结束运行。manager会重新创建一个worker进程。此选项用来防止worker进程内存溢出 (onConnect/onClose不增加计数)
 *
 * 1 - 平均分配
 * 3 - 抢占式分配           每次都是空闲的worker进程获得数据，worker进程内发生onConnect/onReceive/onClose/onTimer会将worker进程标记为忙，不再接受新的请求。reactor会将新请求投递给其他状态为闲的worker进程
 * 2 - 按FD取模固定分配。    如果希望每个连接的数据分配给固定的worker进程，dispatch_mode需要设置为2
 * @property int $dispatch_mode     worker进程数据包分配模式 ，，默认为取模(dispatch=2)
 *
 * @property int $open_cpu_affinity     CPU亲和设置
 * @property int $open_tcp_nodelay      TCP_NoDelay启用 @see https://www.zhihu.com/question/42308970/answer/94248252
 * @property int $tcp_defer_accept      此参数设定一个秒数，当客户端连接连接到服务器时，在约定秒数内并不会触发accept，直到有数据发送，或者超时时才会触发
 * @property int $log_file              指定swoole错误日志文件。在swoole运行期发生的异常信息会记录到这个文件中。默认会打印到屏幕。
 *
 * 心跳检测机制
 * @property int $heartbeat_check_interval  每隔多少秒检测一次，单位秒，Swoole会轮询所有TCP连接，将超过心跳时间的连接关闭掉
 * @property int $heartbeat_idle_time       TCP连接的最大闲置时间，单位s , 如果某fd最后一次发包距离现在的时间超过这个时间，会把这个连接关闭。
 *
 * @package driphp\service
 */
final class Swoole extends Component
{

    const IPV4_ALL = '0.0.0.0'; # IPv4监听所有地址
    const IPV4_LOCAL = '127.0.0.1'; # IPv4监听本机地址
    const IPV6_ALL = '::'; # IPv6监听所有地址
    const IPV6_LOCAL = '::1'; # IPv6监听本机地址

    protected function initialize()
    {
    }

    /**
     * @var Server Server实例,一个PHP程序内只能创建启动一个
     */
    private $server = null;
    /**
     * @var array Server运行时的各项参数
     */
    protected $config = [
        'max_conn' => 1024,
        'daemonize' => 0,
        'reactor_num' => 2,
        'worker_num' => 4,
        'backlog' => 128,
        'max_request' => 2000,

        'dispatch_mode' => 2,

        'open_cpu_affinity' => 1,
        'open_tcp_nodelay' => 1,
        'tcp_defer_accept' => 5,
        'log_file' => DRI_PATH_FRAMEWORK . 'runtime/swoole.log',

        'heartbeat_check_interval' => 30,
        'heartbeat_idle_time' => 60,
    ];

    private $events = [
        'connect' => null,
        'receive' => null,
        'close' => null,
        'start' => null,
    ];

    /**
     * 获取Server实例
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }


    public function newTcpServer(string $ip = self::IPV4_ALL, int $port = 20183, int $type = SWOOLE_TCP, bool $useProcessMode = true): Swoole
    {
        $this->server = new Server($ip, $port, $useProcessMode ? SWOOLE_PROCESS : SWOOLE_BASE, $type);
        return $this;
    }

    public function onConnect(callable $callback)
    {
        $this->events['connect'] = function (Server $server, int $fd) use ($callback) {
            $callback($server, $fd);
        };
        return $this;
    }

    public function onReceive(callable $callback)
    {
        $this->events['receive'] = function (Server $server, int $fd, int $fromId, string $data) use ($callback) {
            $callback($server, $fd, $fromId, $data);
        };
        return $this;
    }

    public function onClose(callable $callback)
    {
        $this->events['close'] = function (Server $server, int $fd) use ($callback) {
            $callback($server, $fd);
        };
        return $this;
    }

    /**
     * Server启动在主进程的主线程回调此函数
     * @param callable $callback
     * @return $this
     */
    public function onStart(callable $callback)
    {
        $this->events['start'] = $callback;
        return $this;
    }

    public function start(bool $daemonize = false): void
    {
        foreach ($this->events as $event => $callback) {
            $this->server->on($event, $callback);
        }
        $this->config['daemonize'] = $daemonize ? 1 : 0;
        $this->server->set($this->config);
        $this->server->start();
    }
}