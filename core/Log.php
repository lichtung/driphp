<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/15 0015
 * Time: 20:11
 */
declare(strict_types=1);


namespace sharin\core;

use sharin\Component;


/**
 * Class Log 日志记录器
 *
 * @version 0.0
 *
 * @method warn(mixed $message, bool $saveImmediately = false) static
 * @method debug(mixed $message, bool $saveImmediately = false) static
 * @method info(mixed $message, bool $saveImmediately = false) static
 * @method fatal(mixed $message, bool $saveImmediately = false) static
 *
 * @package sharin\core
 */
class Log extends Component
{
    protected $config = [
        'format' => 'Ymd',
        'level' => self::ALL,
    ];


    //5种正常级别
    const DEBUG = 0b1;
    const INFO = 0b10;
    const WARN = 0b100;
    const FATAL = 0b1000;
    const ALL = 0b1111;
    const OFF = 0;
    /**
     * @var array 日志信息
     */
    private static $records = [
        'default' => [],
    ];

    /**
     * 获取日志记录器
     * @param string $category 日志分类
     * @return Log
     */
    public static function getLogger(string $category = 'default')
    {
        static $_loggers = [];
        if (!isset($_loggers[$category])) {
            $_loggers[$category] = new self($category);
            isset(self::$records[$category]) or self::$records[$category] = [];
        }
        return $_loggers[$category];
    }

    protected function initialize(string $category = 'default')
    {
        $this->level = $config['level'] ?? self::ALL;
        $this->category = $category;
    }

    private $category = 'default';
    private $level = self::ALL;


    public static function save()
    {
        $date = date('Ymd');

        if (SR_IS_CLI) {
            $title = '[IS_CLIENT]';
        } else {
            $now = date('Y-m-d H:i:s');
            $ip = Request::getInstance()->getClientIP();
            $title = "[{$now}] {$ip} {$_SERVER['REQUEST_URI']}";
        }

        foreach (self::$records as $name => & $logs) {
            if ($logs) {
                $message = implode(PHP_EOL, $logs);
                is_dir($directoryName = dirname($destination = SR_PATH_RUNTIME . "log/{$name}/{$date}.log"))
                or mkdir($directoryName, 0777, true);
                SR_IS_CLI or $message = "{$title}\n{$message}";
                error_log($message . PHP_EOL, 3, $destination);
                $logs = [];
            }
        }
    }

    /**
     * record text message to log
     * @param string|array $message message to log
     * @param int $level
     * @param boolean $saveImmediately It will store the logs to file immediately instead of waiting script shutdown if set to true
     * @return void
     */
    public function record($message, int $level = self::INFO, bool $saveImmediately = false)
    {
        if ($level & $this->level) {
            //无论是静态调用还是实例化调用，都会得到index为2的位置

            if ($location = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[2] ?? false) {
                $file = isset($location['file']) ? $location['file'] : '';
                $line = isset($location['line']) ? $location['line'] : '';
                $location = "{$file}<{$line}>";
            }

            $level = self::_level2str($level);
            $message = is_array($message) ? var_export($message, true) : (string)$message;
            is_array($location) and $location = 'NO LOCATION';
            $now = date('Y-m-d H:i:s');
            self::$records[$this->category][] = "[{$level} {$now}]{$location}: \n{$message}\n";
            //命令行模式下立即保存
            $saveImmediately and self::save();
        }
    }


    public function getLog($fetchAll = false)
    {
        return $fetchAll ? self::$records : (isset(self::$records[$this->category]) ? self::$records[$this->category] : []);
    }

    /**
     * @param string $level
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $level, array $arguments)
    {
        return call_user_func_array(
            [static::getInstance(), 'record'],
            [
                $arguments[0],
                self::_str2level($level),
                empty($arguments[1]) ? false : $arguments[1]
            ]);
    }

    /**
     * @param string $level
     * @param array $arguments
     * @return void
     */
    public function __call(string $level, array $arguments)
    {
        $this->record($arguments[0], self::_str2level($level), isset($arguments[1]) ? $arguments[1] : false);
    }

    /**
     * level identify to level number
     * @param string $str
     * @return int|mixed
     */
    private static function _str2level($str)
    {
        $str = strtolower($str);
        static $_map = [
            'debug' => self::DEBUG,
            'info' => self::INFO,
            'warn' => self::WARN,
            'fatal' => self::FATAL,
            'all' => self::ALL,
        ];
        return isset($_map[$str]) ? $_map[$str] : self::ALL;
    }

    /**
     * level number to level identify
     * @param int $level
     * @return string
     */
    private static function _level2str($level)
    {
        switch ($level) {
            case self::DEBUG:
                return 'DEBUG';
                break;
            case self::INFO:
                return 'INFO';
                break;
            case self::WARN:
                return 'WARN';
                break;
            case self::FATAL;
                return 'FATAL';
                break;
            case self::ALL:
                return 'ALL';
                break;
            default:
                return 'UNKNOWN LEVEL';
        }
    }

}

register_shutdown_function(function () {
    Log::save();
});