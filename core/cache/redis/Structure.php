<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 17:17
 */

namespace driphp\core\cache\redis;

use driphp\core\cache\Redis;

/**
 * Class Structure 数据结构
 * @package driphp\core\cache\redis
 */
abstract class Structure
{


    /** @var Redis */
    protected $context;
    /** @var string */
    protected $name;
    /** @var \Redis */
    protected $adapter;

    /**
     * Structure constructor.
     * @param string $name
     * @param Redis $context
     * @throws \driphp\throws\core\RedisConnectException
     */
    public function __construct(string $name, Redis $context)
    {
        $this->name = $name;
        $this->context = $context;
        $this->adapter = $context->getAdapter();
    }

}