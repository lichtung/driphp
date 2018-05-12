<?php
/**
 * Created by linzh.
 * Email: 784855684@qq.com
 * Date: 5/31/17 4:57 PM
 */

namespace sharin\library;


/**
 * Class Quene
 *
 * @method bool push($value) （尾部）入队
 * @method unshift($value) （头部）入队
 * @method shift() （头部）出队
 * @method bool clean() 清空队列
 * @method mixed first() 获取列头
 * @method mixed last() 获取列尾
 * @method int length() 获取长度
 *
 * @package lite\library
 */
class Queue
{
    /**
     * 队列驱动
     * @var \sharin\library\queue\QueueInterface
     */
    private $adapter = null;
    /**
     * 队列名称
     * @var string
     */
    private $name = '';

    public function __construct(string $qname, string $adapter)
    {
        $this->name = $qname;
        $this->adapter = new $adapter($qname);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->adapter, $name], $arguments);
    }


}