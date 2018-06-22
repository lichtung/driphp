<?php
/**
 * User: linzhv@qq.com
 * Date: 12/04/2018
 * Time: 22:20
 */
declare(strict_types=1);


namespace driphp\core\logger;


use driphp\Component;
use driphp\DriverInterface;
use driphp\core\Logger;
use driphp\core\Request;

/**
 * Class FileHandler
 *
 * @package driphp\core\logger
 */
class FileHandler implements DriverInterface, LoggerInterface
{
    /**
     * @var Logger
     */
    protected $context = null;
    protected $config = [];

    /**
     * @var array 日志信息
     */
    private static $records = [
        'default' => [],
    ];

    /**
     * FileHandler constructor.
     * @param array $config
     * @param Component|Logger $context
     */
    public function __construct(array $config, Component $context)
    {
        # web模式下脚本结束自动保存
        if (!DRI_IS_CLI) register_shutdown_function([$this, 'store']);
        $this->config = $config;
        $this->context = $context;
    }

    public function store()
    {
        $yearMonth = date('Ym');
        $day = date('d');

        if (DRI_IS_CLI) {
            $title = '[CLI-MODE]';
        } else {
            $now = date('Y-m-d H:i:s');
            $ip = Request::getInstance()->getClientIP();
            $title = "[{$now}] {$ip} {$_SERVER['REQUEST_URI']}";
        }

        foreach (self::$records as $name => & $logs) {
            if ($logs) {
                $message = implode(PHP_EOL, $logs);
                is_dir($directoryName = dirname($destination = DRI_PATH_RUNTIME . "log/{$yearMonth}/{$day}/{$name}.log")) or mkdir($directoryName, 0777, true);
                DRI_IS_CLI or $message = "{$title}\n{$message}";
                error_log($message . PHP_EOL, 3, $destination);
                $logs = [];
            }
        }
    }

    /**
     * record text message to log
     * @param array|string $message 需要记录的信息
     * @param int $level
     * @param string $tag
     * @param bool $storeImmediately 立即保存日志到文件中(命令行中自动保存) It will store the logs to file immediately instead of waiting script shutdown if set to true
     * @return bool
     */
    public function record($message, int $level = Logger::INFO, string $tag = 'default', bool $storeImmediately = false): bool
    {
        if ($level & $this->context->getAllowLevel()) {
            //无论是静态调用还是实例化调用，都会得到index为2的位置
            # 获取调用的位置
            if ($location = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)[2] ?? false) {
                $file = $location['file'] ?? '';
                $line = $location['line'] ?? '';
                $location = "{$file}<{$line}>";
            } else {
                $location = 'NO LOCATION';
            }

            $now = date('Y-m-d H:i:s');
            $levelName = Logger::LEVEL[$level];

            isset(self::$records[$tag]) or self::$records[$tag] = [];

            is_array($message) and $message = var_export($message, true);
            self::$records[$tag][] = "[{$levelName} {$now}]{$location}: \n{$message}\n";

            # 命令行模式下／参数三为true 立即保存
            # 注意命令行模式下应该避免频繁记录日志
            if ($storeImmediately or DRI_IS_CLI) {
                $this->store();
            }
            return true;
        }
        return false;
    }

    public function debug($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::DEBUG, $tag, $storeImmediately);
    }

    public function info($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::INFO, $tag, $storeImmediately);
    }

    public function notice($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::NOTICE, $tag, $storeImmediately);
    }

    public function warning($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::WARNING, $tag, $storeImmediately);
    }

    public function error($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::ERROR, $tag, $storeImmediately);
    }

    public function critical($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::CRITICAL, $tag, $storeImmediately);
    }

    public function alert($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::ALERT, $tag, $storeImmediately);
    }

    public function emergency($message, string $tag = 'default', bool $storeImmediately = false): bool
    {
        return $this->record($message, Logger::EMERGENCY, $tag, $storeImmediately);
    }

//    public function __call($name, $arguments)
//    {
//        return call_user_func_array([$this, 'record'], [
//            $arguments[0],
//            Logger::LEVEL_MAP[$name],
//            $arguments[1],
//            $arguments[2],
//        ]);
//    }
}