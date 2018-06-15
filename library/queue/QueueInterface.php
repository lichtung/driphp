<?php
/**
 * Created by linzh.
 * Email: 784855684@qq.com
 * Date: 5/31/17 5:00 PM
 */

namespace driphp\library\queue;

/**
 * Interface QueueInterface 队列实现接口
 * @package driphp\library\quene
 */
interface QueueInterface
{
    /**
     * （尾部）入队
     * @param mixed $value
     * @return int
     */
    public function push($value);

    /**
     * （尾部）出队
     * @return mixed
     */
    public function pop();

    /**
     * （头部）入队
     * @param $value
     * @return int
     */
    public function unshift($value);

    /**
     * （头部）出队
     * @return mixed
     */
    public function shift();

    /**
     * 清空队列
     * @return void
     */
    public function clean();

    /**
     * 获取列头
     * @return mixed
     */
    public function first();

    /**
     * 获取列尾
     * @return mixed
     */
    public function last();

    /**
     * 获取长度
     * @return int
     */
    public function length();
}