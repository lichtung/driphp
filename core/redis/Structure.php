<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 17:17
 */

namespace driphp\core\redis;

use driphp\core\RedisManager;

/**
 * Class Structure 数据结构
 * @package driphp\core\redis
 */
abstract class Structure
{


    /** @var RedisManager */
    protected $context;
    /** @var string */
    protected $name;
    /** @var \Redis */
    protected $adapter;

    public function __construct(string $name, RedisManager $context)
    {
        $this->name = $name;
        $this->context = $context;
        $this->adapter = $context->getAdapter();
    }

}