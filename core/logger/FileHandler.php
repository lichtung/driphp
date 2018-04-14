<?php
/**
 * User: linzhv@qq.com
 * Date: 12/04/2018
 * Time: 22:20
 */
declare(strict_types=1);


namespace sharin\core\logger;


use sharin\DriverInterface;
use sharin\core\Logger;
use sharin\core\Request;

/**
 * Class FileHandler
 * @method bool debug($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool info($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool notice($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool warning($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool error($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool critical($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool alert($message, string $tag = 'default', bool $storeImmediately = false)
 * @method bool emergency($message, string $tag = 'default', bool $storeImmediately = false)
 *
 * @package sharin\core\logger
 */
class FileHandler implements DriverInterface, LoggerInterface
{
    /**
     * @var Logger
     */
    protected $context = null;
    protected $config = [];
    private $loggerName = 'default';

    /**
     * @param array $config
     * @param Logger $context
     * @return DriverInterface
     */
    public function init(array $config, $context): DriverInterface
    {
        $this->config = $config;
        $this->context = $context;
        return $this;
    }

    /**
     * @var array 日志信息
     */
    private static $records = [
        'default' => [],
    ];


    public function __construct()
    {
        # web模式下脚本结束自动保存
        if (!SR_IS_CLI) register_shutdown_function([$this, 'store']);
    }

    public function store(): void
    {
        $yearMonth = date('Ym');
        $day = date('d');

        if (SR_IS_CLI) {
            $title = '[CLI-MODE]';
        } else {
            $now = date('Y-m-d H:i:s');
            $ip = Request::getInstance()->getClientIP();
            $title = "[{$now}] {$ip} {$_SERVER['REQUEST_URI']}";
        }

        foreach (self::$records as $name => & $logs) {
            if ($logs) {
                $message = implode(PHP_EOL, $logs);
                is_dir($directoryName = dirname($destination = SR_PATH_RUNTIME . "log/{$yearMonth}/{$day}/{$name}.log")) or mkdir($directoryName, 0777, true);
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
     * @param string $tag
     * @param boolean $storeImmediately It will store the logs to file immediately instead of waiting script shutdown if set to true
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
            if ($storeImmediately or SR_IS_CLI) {
                $this->store();
            }
            return true;
        }
        return false;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this, 'record'], [
            $arguments[0],
            Logger::LEVEL_MAP[$name],
            $arguments[1],
            $arguments[2],
        ]);
    }
}