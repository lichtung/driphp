<?php
/**
 * User: linzhv@qq.com
 * Date: 19/03/2018
 * Time: 15:26
 */
declare(strict_types=1);


namespace driphp\service\swoole;


interface WorkerEventHandlerInterface
{

    public function onStart();

    public function onConnect();

    public function onClose();

}