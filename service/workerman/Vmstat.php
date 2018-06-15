<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 11:06
 */
declare(strict_types=1);


namespace driphp\service\workerman;


use driphp\service\Workerman;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * Class Vmstat
 * @method Vmstat getInstance(string $index = '') static
 * @package driphp\service\workerman
 */
class Vmstat extends Workerman
{
    protected $config = [
        'host' => '0.0.0.0',
        'port' => 12001,
        'interval' => 3,
    ];

    public function init()
    {
        $worker = new Worker("Websocket://{$this->config['host']}:{$this->config['port']}");

        $worker->name = 'VMStatWorker';
        $worker->onWorkerStart = function ($worker) {
            // 把进程句柄存储起来，在进程关闭的时候关闭句柄
            $worker->process_handle = popen("vmstat {$this->config['interval']}", 'r');
            if ($worker->process_handle) {
                $process_connection = new TcpConnection($worker->process_handle);
                $process_connection->onMessage = function ($process_connection, $data) use ($worker) {
                    foreach ($worker->connections as $connection) {
                        call_user_func([$connection, 'send'], $data);
                    }
                };
            } else {
                echo "vmstat 1 fail\n";
            }
        };

        // 进程关闭时
        $worker->onWorkerStop = function ($worker) {
            try {
                @shell_exec('killall vmstat');
                @pclose($worker->process_handle);
            } catch (\Throwable  $t) {

            }
        };
        /**
         * @param TcpConnection $connection
         * @return void
         */
        $worker->onConnect = function ($connection) {
//            $connection->send("procs -----------memory---------- ---swap-- -----io---- -system-- ----cpu----\n");
//            $connection->send("r  b   swpd   free   buff  cache   si   so    bi    bo   in   cs us sy id wa\n");
        };
    }
}