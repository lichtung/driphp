<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 21:50
 */
declare(strict_types=1);


namespace sharin\core;

use sharin\core\interfaces\ExceptionHandlerInterface;
use sharin\Kernel;
use Throwable;
use sharin\Component;
use sharin\SharinException;

/**
 * Class Initializer 初始化器
 * @method Initializer getInstance(...$param) static
 * @package sharin\core
 */
class Initializer extends Component
{
    protected $config = [
        'timezone_zone' => 'Asia/Shanghai',
        'shutdown_handler' => null,
        'exception_handler' => null,
        'session.save_handler' => 'files',# redis
        'session.save_path' => SR_PATH_RUNTIME,# tcp://127.0.0.1:6379
        'session.gc_maxlifetime' => 3600,
        'session.cache_expire' => 3600,
    ];

    protected function __construct(string $index = '')
    {
        parent::__construct($index);
        date_default_timezone_set($this->config['timezone_zone']) or die('timezone set failed!');
        # ini_set('expose_php', 'Off'); # ini_set 无效，需要修改 php.ini 文件
        false === ini_set('session.save_handler', $this->config['session.save_handler']) and die('set session.save_handler failed');
        false === ini_set('session.save_path', $this->config['session.save_path']) and die('set session.save_path failed');
        false === ini_set('session.gc_maxlifetime', (string)$this->config['session.gc_maxlifetime']) and die('set session.gc_maxlifetime failed');
        false === ini_set('session.cache_expire', (string)$this->config['session.cache_expire']) and die('set session.cache_expire failed');
    }

    /**
     * Register a function for execution on shutdown
     * @return bool always return true
     */
    public function registerShutdownHandler(): bool
    {
        is_callable($this->config['shutdown_handler']) and register_shutdown_function($this->config['shutdown_handler']);
        return true;
    }

    /**
     * @return void
     */
    public function registerExceptionHandler()
    {
//        $handler = $this->config['exception_handler'] ?? '';
//        if ($handler and class_implements($handler, ExceptionHandlerInterface::class)) {
//            $handler = Kernel::factory($handler);
//            $errorHandler = [$handler, 'error'];
//            $exceptionHandler = [$handler, 'exception'];
//        }
        set_error_handler(function (int $code, string $message, string $file, int $line) {
            SharinException::dispose(null, $code, $message, $file, $line);
        });
        set_exception_handler(function (Throwable $e) {
            SharinException::dispose($e);
        });
    }

    /**
     * 开启 RESTful 支持
     * @return bool 开启成功返回true，无需开启返回false
     */
    public function supportRestful(): bool
    {
        # restful support
        if (SR_REQUEST_METHOD and SR_REQUEST_METHOD !== 'GET' and SR_REQUEST_METHOD !== 'POST') {
            # GET       Get resource from server(one or more)
            # POST      Create resource
            # PUT       Update resource with full properties
            # PATCH     Update resource with some properties
            # DELETE    Delete resource
            if ($_input = file_get_contents('php://input')) {
                parse_str($_input, $_request_data);
                $_request_data and $_REQUEST = array_merge($_REQUEST, $_request_data);
            }
            return true;
        }
        return false;
    }

}