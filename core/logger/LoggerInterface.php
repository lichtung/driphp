<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 09:53
 */
declare(strict_types=1);


namespace driphp\core\logger;


use driphp\core\Logger;

/**
 * Interface LoggerInterface
 *
 * @package driphp\core\logger
 */
interface LoggerInterface
{
    /**
     * @param string|array $message
     * @param int $level 日志级别
     * @param string $tag
     * @param bool $saveImmediately 是否立即保存，命令行模式下会自动调用
     * @return bool 记录成功返回true，记录失败（级别过低）返回false
     */
    public function record($message, int $level = Logger::INFO, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录详细的debug信息
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function debug($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录具体的事件，如用户登录、sql查询等
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function info($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录需要注意的事件
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function notice($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录警告事件，如调用了弃用的API，使用了不推荐的代码等
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function warning($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录运行时的错误信息
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function error($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录至关重要的信息，如检测到异常的抛出、组件不存在、类／函数不存在等
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function critical($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录极其重要的信息，如数据库不可用、第三方服务挂掉的情况，如果条件许可发短信和邮件联系管理员
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function alert($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 记录最高级别的日志，必须立即通知管理员处理相关错误
     * @param string|array $message
     * @param string $tag
     * @param bool $saveImmediately
     * @return mixed
     */
    public function emergency($message, string $tag = 'default', bool $saveImmediately = false): bool;

    /**
     * 立即存储数据
     * @return void
     */
    public function store();
}