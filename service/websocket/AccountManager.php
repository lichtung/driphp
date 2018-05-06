<?php
/**
 * User: linzhv@qq.com
 * Date: 06/05/2018
 * Time: 17:06
 */
declare(strict_types=1);


namespace sharin\service\websocket;


use sharin\service\WebSocketManager;

/**
 * Class AccountManager WebSocket账号管理
 * @package sharin\service\websocket
 */
class AccountManager
{
    /** @var WebSocketManager $manager */
    protected $manager;

    private $fd2name = [];
    private $name2fd = [];

    /**
     * 设置上下文环境
     * @param WebSocketManager $manager
     * @return $this
     */
    public function __construct(WebSocketManager $manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * 登记账号
     * @param string $userName
     * @param string $token
     * @return bool
     */
    public function validate(string $userName, string $token): bool
    {
        return !empty($userName) and !empty($token);
    }

    /**
     * 登记账号
     * @param string $name
     * @param int $fd
     * @return void
     */
    public function register(string $name, int $fd)
    {
        $this->fd2name[$fd] = $name;
        $this->name2fd[$name] = $fd;
    }

    /**
     * 判断连接是否为WebSocket客户端
     * @see https://wiki.swoole.com/wiki/page/490.html
     * @param int $fd
     * @return bool
     */
    public function isWebSocketClient(int $fd)
    {
        $info = $this->manager->getServer()->connection_info($fd);
        return isset($info['websocket_status']);
    }

    /**
     * 更具名称获取fd
     * @param string $name
     * @return int 如果账号未激活，则返回0
     */
    public function getFdByName(string $name): int
    {
        if ($fd = $this->name2fd[$name] ?? 0) {
            if ($this->manager->getServer()->exist($fd)) {
                return $fd;
            } else {
                $this->unregister($name);
            }
        }
        return 0;
    }

    /**
     * 解除登记
     * @param string $name
     * @return void
     */
    public function unregister(string $name)
    {
        if ($fd = $this->name2fd[$name] ?? 0) {
            $this->manager->closeWith($fd, 2);
            unset($this->fd2name[$fd]);
        }
        unset($this->name2fd[$name]);
    }

}