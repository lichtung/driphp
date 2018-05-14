<?php
/**
 * User: linzhv@qq.com
 * Date: 19/03/2018
 * Time: 14:57
 */
declare(strict_types=1);


namespace sharin\service\swoole;

use Swoole\Server;

/**
 * Interface ServerEventsHandlerInterface Server事件处理器
 *
 *
 * @package sharin\service\swoole
 */
interface ServerEventsHandlerInterface
{
    /**
     * Server启动在主进程的主线程处理,仅允许echo、打印Log、修改进程名称
     * @param Server $server
     * @return void
     */
    public function onStart(Server $server): void;

    /**
     * Server正常结束时发生,强制kill或者Ctrl+C进程不会回调onShutdown
     * @param Server $server
     * @return void
     */
    public function onShutdown(Server $server): void;
}