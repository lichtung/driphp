<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 17:40
 */
declare(strict_types=1);

namespace sharin\service\workerman\socketio;
/**
 * Class Message 代表通信过程中的消息体部
 *
 * @property int $from
 * @property int $to
 * @property int $type
 * @property string $content
 *
 * @package lite\service\socketio
 */
class Message
{
    /**
     * @var array 消息的组成部分
     */
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}