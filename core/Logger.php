<?php
/**
 * User: linzhv@qq.com
 * Date: 12/04/2018
 * Time: 22:16
 */
declare(strict_types=1);


namespace sharin\core;


use sharin\Component;
use sharin\core\logger\FileHandler;
use sharin\core\logger\LoggerInterface;
use sharin\DriverInterface;
use sharin\throws\core\BadLoggerLevelException;
use sharin\throws\core\logger\MessageEmptyException;

/**
 * Class Logger 日志记录器
 *
 * @method debug(mixed $message, bool $saveImmediately = false) static 记录详细的debug信息
 * @method info(mixed $message, bool $saveImmediately = false) static 记录具体的事件，如用户登录、sql查询等
 * @method notice(mixed $message, bool $saveImmediately = false) static 记录需要注意的事件
 * @method warning(mixed $message, bool $saveImmediately = false) static 记录警告事件，如调用了弃用的API，使用了不推荐的代码等
 * @method error(mixed $message, bool $saveImmediately = false) static 记录运行时的错误信息
 * @method critical(mixed $message, bool $saveImmediately = false) static 记录至关重要的信息，如检测到异常的抛出、组件不存在、类／函数不存在等
 * @method alert(mixed $message, bool $saveImmediately = false) static 记录极其重要的信息，如数据库不可用、第三方服务挂掉的情况，如果条件许可发短信和邮件联系管理员
 * @method emergency(mixed $message, bool $saveImmediately = false) static 记录最高级别的日志，必须立即通知管理员处理相关错误
 *
 * @method LoggerInterface drive()
 *
 * @package sharin\core
 */
class Logger extends Component
{
    /**
     * Detailed debug information
     */
    const DEBUG = 0b1;
    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 0b10;
    /**
     * Uncommon events
     */
    const NOTICE = 0b100;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 0b1000;
    /**
     * Runtime errors
     */
    const ERROR = 0b10000;
    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 0b100000;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 0b1000000;

    /**
     * Urgent alert.
     */
    const EMERGENCY = 0b10000000;
    /** 全部level */
    const ALL = 0b11111111;
    /** 全部不记录 */
    const OFF = 0;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * This is a static variable and not a constant to serve as an extension point for custom levels
     *
     * @var string[] $levels Logging levels with the levels as key
     */
    const LEVEL = [
        self::DEBUG => 'debug',
        self::INFO => 'info',
        self::NOTICE => 'notice',
        self::WARNING => 'warning',
        self::ERROR => 'error',
        self::CRITICAL => 'critical',
        self::ALERT => 'alert',
        self::EMERGENCY => 'emergency',
    ];

    const LEVEL_MAP = [
        'debug' => self::DEBUG,
        'info' => self::INFO,
        'notice' => self::NOTICE,
        'warning' => self::WARNING,
        'error' => self::ERROR,
        'critical' => self::CRITICAL,
        'alert' => self::ALERT,
        'emergency' => self::EMERGENCY,
    ];


    protected $config = [
        'format' => 'Ymd',
        'level' => self::ALL,
        'drivers' => [
            'default' => [
                'name' => FileHandler::class,
                'config' => [],
            ],
        ],
    ];

    private $allowLevel = self::ALL;

    private $loggerName = 'default';

    /**
     * @return int
     */
    public function getAllowLevel(): int
    {
        return $this->allowLevel;
    }

    /**
     * @param int $allowLevel
     * @return $this
     */
    public function setAllowLevel(int $allowLevel): Logger
    {
        $this->allowLevel = $allowLevel;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoggerName(): string
    {
        return $this->loggerName;
    }

    /**
     * @param string $loggerName
     * @return Logger
     */
    public function setLoggerName(string $loggerName): Logger
    {
        $this->loggerName = $loggerName;
        return $this;
    }

    /**
     * @param string $loggerName
     * @return Logger|LoggerInterface
     */
    public static function getInstance(string $loggerName = 'default')
    {
        /** @var Logger $instance */
        $instance = parent::getInstance();
        $instance->setLoggerName($loggerName);
        return $instance;
    }


    /**
     * @param string $level
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $level, array $arguments)
    {
        return call_user_func_array([static::getInstance(), $level], [
            $arguments[0],
            $arguments[1] ?? false
        ]);
    }


    /**
     * @param string $level
     * @param array $arguments
     * @return void
     * @throws
     */
    public function __call(string $level, array $arguments)
    {
        if (!isset(self::LEVEL_MAP[$level])) {
            throw new BadLoggerLevelException($level);
        }
        if (empty($arguments[0])) throw new MessageEmptyException('message to record should not be empty');
        $this->drive()->record($arguments[0], self::LEVEL_MAP[$level], $this->loggerName, $arguments[1] ?? false);
    }


}