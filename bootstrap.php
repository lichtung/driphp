<?php
/**
 * User: linzhv@qq.com
 * Date: 09/04/2018
 * Time: 23:13
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *                 ┏━┓    ┏━┓
 *                ┏┛ ┻━━━━┛ ┻━┓
 *                ┃           ┃ 　
 *   ┏┓      ┏┓   ┃           ┃
 *  ┏┛┻━━━━━━┛┻━┓ ┃ ==    ==  ┃
 *  ┃           ┃ ┃           ┃   ┏┓      ┏┓
 *  ┃ ==    ==  ┃ ┃     ^     ┃  ┏┛┻━━━━━━┛┻━┓
 *  ┃           ┃ ┃           ┃  ┃ ==    ==  ┃
 *  ┃     ^     ┃ ┗━━┓      ┏━┛  ┃     ^     ┃
 *  ┗━━┓      ┏━┛    ┃      ┃    ┗━━┓      ┏━┛
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Description: Sharing the sharin framework for web developers of beginner.
 */
declare(strict_types=1);

namespace {

    use sharin\Kernel;
    const SR_VERSION = 'Asura'; # 首字母A-Z

    define('SR_MICROTIME', ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)));
    define('SR_MEMORY', memory_get_usage());# byte

    defined('SR_DEBUG_ON') or define('SR_DEBUG_ON', true); #  debug模式默认开启
    defined('SR_LOAN_BALANCE_ON') or define('SR_LOAN_BALANCE_ON', false);# 负载均衡模式默认关闭（开启时候需要手动设置HOST名称）
    defined('SR_PROJECT_NAME') or define('SR_PROJECT_NAME', '');# 项目名称（项目所在目录的名称，如 idea.sharin.com/ ）

    # environment constant
    const SR_IS_CLI = PHP_SAPI === 'cli'; # is client environment?
    define('SR_IS_WIN', false !== stripos(PHP_OS, 'WIN'));# is windows?
    define('SR_IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    # request method
    define('SR_REQUEST_METHOD', strtoupper($_SERVER['REQUEST_METHOD'] ?? ''));//'GET', 'DELETE'，'POST'，'PUT' 'PATCH' ...

    # directory constant
    define('SR_PATH_ROOT', dirname(__DIR__) . '/'); # the parent directory of project and framework
    const SR_PATH_FRAMEWORK = __DIR__ . '/';    # framework directory
    const SR_PATH_PROJECT = SR_PATH_ROOT . SR_PROJECT_NAME . '/'; # project directory
    const SR_PATH_DATA = SR_PATH_PROJECT . 'data/'; # data directory to store dynamic config or file-based data
    const SR_PATH_VENDOR = SR_PATH_PROJECT . 'vendor/'; # vendor directory for project
    const SR_PATH_RUNTIME = SR_PATH_PROJECT . 'runtime/'; # to store temporary, cache file
    const SR_PATH_PUBLIC = SR_PATH_PROJECT . 'public/'; # public resource (css, js, image...) and entry script
    const SR_PATH_CONTROLLER = SR_PATH_PROJECT . 'controller/'; # to store controller class
    const SR_PATH_MODEL = SR_PATH_PROJECT . 'model/'; # to store model class
    const SR_PATH_VIEW = SR_PATH_PROJECT . 'view/'; # to store view template


    # charset
    const SR_CHARSET_UTF8 = 'UTF-8';
    const SR_CHARSET_GBK = 'GBK';
    const SR_CHARSET_ASCII = 'ASCII';
    const SR_CHARSET_GB2312 = 'GB2312';
    const SR_CHARSET_LATIN1 = 'ISO-8859-1';# Latin1 is the alia of ISO-8859-1

    const SR_TYPE_BOOL = 'boolean';
    const SR_TYPE_INT = 'integer';
    const SR_TYPE_FLOAT = 'double'; # gettype(1.7) === 'double'
    const SR_TYPE_STR = 'string';
    const SR_TYPE_ARRAY = 'array';
    const SR_TYPE_OBJ = 'object'; # gettype(function (){})
    const SR_TYPE_RESOURCE = 'resource';
    const SR_TYPE_NULL = 'NULL'; # gettype(null) === 'NULL'
    const SR_TYPE_UNKNOWN = 'unknown type';

    if (SR_DEBUG_ON) {
        require __DIR__ . '/include/debug.php';
        SR_IS_CLI or register_shutdown_function(function () {
//            if (class_exists(Response::class)) echo Response::getInstance();
            Kernel::status('shutdown');
            require(__DIR__ . '/include/trace.php');
        });
    } else {
        function dumpon(...$a)
        {
        }

        function dumpout(...$a)
        {
        }
    }
}

namespace sharin {

    use sharin\core\database\driver\Driver;
    use sharin\core\Dispatcher;
    use sharin\core\Logger;
    use sharin\core\Request;
    use sharin\core\response\JSON;
    use sharin\core\Route;
    use sharin\throws\core\DriverNotDefinedException;
    use Throwable;
    use sharin\throws\core\ClassNotFoundException;

    /**
     * Class SharinException 内置异常
     * @package sharin
     */
    class SharinException extends \Exception
    {
        /**
         * SharinException constructor.
         * @param string $message
         * @param int $code
         */
        public function __construct(string $message, int $code = -1)
        {
            parent::__construct($message, $code);
        }

        /**
         * Dispose an throwable and quit
         * @param Throwable|null $throwable
         * @param int $code
         * @param string $message
         * @param string $file
         * @param int $line
         * @return void
         */
        public static function dispose(Throwable $throwable = null, int $code = 0, string $message = '', string $file = '', int $line = 0)
        {
            if (null !== $throwable) {
                $message = $throwable->getMessage();
                $className = get_class($throwable);
                $file = $throwable->getFile();
                $line = (int)$throwable->getLine();
                $traces = $throwable->getTrace();
                $code = $throwable->getCode();
            } else {
                #  $error = error_get_last();
                $className = 'Error';
                $traces = debug_backtrace();
            }
            ob_get_level() and ob_clean();
            Logger::getInstance('throwable')->critical($information = [
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'class' => $className,
            ]);
            if (SR_IS_CLI) {
                var_dump($information);
            } elseif (SR_IS_AJAX) {
                exit(new JSON($information));
            } else {
                if (SR_DEBUG_ON) {
                    require_once __DIR__ . '/include/error.php';
                    _display_error($message, $className, $file, $line, $code, $traces);
                } else {
                    Kernel::template('404');
                }
            }
            exit(1);
        }
    }

    /**
     * Interface InvokeInterface 直线调用
     * @package sharin
     */
    interface InvokeInterface
    {
        /**
         * @return mixed
         */
        public function invoke();

    }

    /**
     * Interface DriverInterface 驱动器器接口
     * @package sharin
     */
    interface DriverInterface
    {
        /**
         * DriverInterface constructor.
         * @param array $config 初始化配置
         * @param Component $context 驱动依附的组件类作为其上下文环境
         */
        public function __construct(array $config, $context);

    }

    /**
     * Class Component 组件类
     * @package sharin
     */
    abstract class Component
    {
        /** @var array $config 组件实例配置 */
        protected $config = [];

        /** @var string $index 默认驱动索引 */
        protected $index = '';

        protected $driverName = '';
        protected $driverConfig = [];

        /** @var array $driverConfig 可用驱动列表 */
        protected $driverPool = [];

        /** @var DriverInterface $driver 驱动实例 */
        protected $driver = null;

        /**
         * 获取驱动索引
         * @return string
         */
        public function getIndex(): string
        {
            return $this->index;
        }

        /**
         * 外部无法实例化组件
         * @param string $connect 驱动名称
         */
        protected function __construct(string $connect = '')
        {
            $className = static::class;
            $this->config = array_merge($this->config, Kernel::getInstance()->config($className));
            if (isset($this->config['drivers'])) {
                $this->driverPool = $this->config['drivers'];
                unset($this->config['drivers']);
            }
            $connect and $this->index = $connect;
        }

        /**
         * 组件单例子
         * @param string $index
         * @return Component
         */
        public static function getInstance(string $index = '')
        {
            static $_instances = [];
            $className = static::class;
            if (!isset($_instances[$key = $index . $className])) {
                /** @var Component $instance */
                $instance = new $className($index);# 因为构造函数私有的缘故不能使用反射
                $_instances[$key] = $instance;
            }
            return $_instances[$key];
        }

        /**
         * @return string
         */
        public function getDriverName(): string
        {
            return $this->driverName;
        }

        /**
         * @return array
         */
        public function getDriverConfig(): array
        {
            return $this->driverConfig;
        }


        /**
         * 加载驱动
         * @return Driver |object 返回驱动实例
         * @throws DriverNotDefinedException 适配器未定义
         * @throws ClassNotFoundException  适配器类不存在
         */
        public function drive()
        {
            if (!isset($this->driver)) {
                if (isset($this->driverPool[$this->index])) {
                    $this->driverName = $this->driverPool[$this->index]['name'];
                    $this->driverConfig = $this->driverPool[$this->index]['config'] ?? [];
                    $this->driver = Kernel::factory($this->driverName, [
                        $this->driverConfig, $this
                    ]);
                } else {
                    throw new DriverNotDefinedException($this->index);
                }
            }
            return $this->driver;
        }

        /**
         * Set config value
         * @param string $name
         * @param mixed $value
         * @return void
         */
        public function __set(string $name, $value)
        {
            $this->config[$name] = $value;
        }

        /**
         * get config value
         * @param string $name
         * @return mixed|null
         */
        public function __get(string $name)
        {
            return $this->config[$name] ?? null;
        }

        /**
         * call the instance method which is not defined
         * @param string $name
         * @param array $arguments
         * @return mixed
         */
        public function __call(string $name, array $arguments)
        {
            return $this->driver ? call_user_func_array([$this->driver, $name], $arguments) : null;
        }

        /**
         * call the class method which is not defined
         * @param string $name
         * @param array $arguments
         * @return mixed
         */
        public static function __callStatic(string $name, array $arguments)
        {
            return call_user_func_array([static::getInstance(), $name], $arguments);
        }

    }

    final class Kernel
    {
        /**
         * @var array 保存所有组件类的配置
         */
        private $config = [

            'timezone_zone' => 'Asia/Shanghai',
            'shutdown_handler' => null,
            'exception_handler' => null,
            'session.save_handler' => 'files',# redis
            'session.save_path' => SR_PATH_RUNTIME,# tcp://127.0.0.1:6379
            'session.gc_maxlifetime' => 3600,
            'session.cache_expire' => 3600,

        ];

        /**
         * @param array $config
         * @return Kernel
         */
        public function init(array $config = null): Kernel
        {
            Kernel::status('init_begin');

            # 类自动装载函数
            spl_autoload_register(function (string $className) {
                $path = (strpos($className, 'sharin\\') === 0) ? SR_PATH_ROOT : SR_PATH_PROJECT;
                $path .= str_replace('\\', '/', $className) . '.php';
                if (is_file($path)) require($path);
            }, false, true) or die('register class loader failed');

            if (isset($config)) foreach ($config as $className => $item) {
                $this->config[$className] = array_merge($this->config[$className] ?? [], $item);
            }
            date_default_timezone_set($this->config['timezone_zone']) or die('timezone set failed!');
            # ini_set('expose_php', 'Off'); # ini_set 无效，需要修改 php.ini 文件
            false === ini_set('session.save_handler', $this->config['session.save_handler']) and die('set session.save_handler failed');
            false === ini_set('session.save_path', $this->config['session.save_path']) and die('set session.save_path failed');
            false === ini_set('session.gc_maxlifetime', (string)$this->config['session.gc_maxlifetime']) and die('set session.gc_maxlifetime failed');
            false === ini_set('session.cache_expire', (string)$this->config['session.cache_expire']) and die('set session.cache_expire failed');

            set_error_handler(function (int $code, string $message, string $file, int $line) {
                SharinException::dispose(null, $code, $message, $file, $line);
            });
            set_exception_handler(function (Throwable $e) {
                SharinException::dispose($e);
            });

            Kernel::status('init_end');
            return $this;
        }

        /**
         * @return void
         * @throws
         */
        public function start()
        {
            if (SR_IS_CLI) return;
            self::status('start');
            $request = Request::getInstance();
            self::status('route');
            $route = Route::getInstance()->parse($request);
            self::status('dispatch');
            Dispatcher::getInstance()->dispatch($route);
            self::status('end');
        }

        /**
         * 获取组件配置
         * @param string $component 组件名称
         * @param array|null $config 组件配置
         * @return array
         */
        public function config(string $component, array $config = null): array
        {
            if (isset($config)) return $this->config[$component] = $config;
            return $this->config[$component] ?? [];
        }

        ######################################### 静态方法区 #############################################################

        public static function getInstance()
        {
            static $_instance = null;
            if (null === $_instance) {
                $_instance = new self();
            }
            return $_instance;
        }

        /**
         * 记录状态或者返回全部状态
         * It will return current record value if tag is not empty ,and whole status records will return if tag is empty
         * @param string|null $tag
         * @return array 如果参数tag为空字符串，则返回全部状态，否则记录当前状态并返回
         */
        public static function status(string $tag = null): array
        {
            static $_status = [
                'onload' => [
                    SR_MICROTIME,
                    SR_MEMORY,
                ],
            ];
            return isset($tag) ? ($_status[$tag] = [microtime(true), memory_get_usage()]) : $_status;
        }

        /**
         * 计算参数哈希值
         * @param mixed $params
         * @return string
         */
        public static function hash($params): string
        {
            $hash = '';
            switch (gettype($params)) {
                case SR_TYPE_ARRAY:
                    foreach ($params as $item) {
                        $hash .= self::hash($item);
                    }
                    break;
                case SR_TYPE_OBJ:
                    $hash = spl_object_hash($params);
                    break;
                case SR_TYPE_RESOURCE:
                    $hash = get_resource_type($params);
                    break;
                default:
                    $hash = serialize($params);
            }
            return $hash;
        }

        /**
         * @param string $className
         * @return \ReflectionClass
         * @throws ClassNotFoundException
         */
        public static function reflect(string $className): \ReflectionClass
        {
            static $_instances = [];
            if (!isset($_instances[$className])) {
                try {
                    $_instances[$className] = new \ReflectionClass($className);
                } catch (Throwable $throwable) { # ReflectionException will be thrown if class does not exist
                    throw new ClassNotFoundException($className);
                }
            }
            return $_instances[$className];
        }

        /**
         * filter dangerous chars
         * @param string $str
         * @return string
         */
        public static function filter(string $str): string
        {
            return htmlentities(strip_tags($str), ENT_QUOTES, 'utf-8');
        }

        /**
         * @param string $className
         * @param array|null $params Constructor parameters in order
         * @return object Return an instance of this class which is separated by parameters
         * @throws ClassNotFoundException
         */
        public static function factory(string $className, array $params = null)
        {
            static $_instances = [];
            $key = $className;
            isset($params) and $key .= self::hash($params);
            if (!isset($_instances[$key])) {
                $_instances[$key] = $params ? self::reflect($className)->newInstanceArgs($params) : new $className();
            }
            return $_instances[$key];
        }


        /**
         * load template with variables
         * @param string $tpl It is the path of template if the fourth parameter is true, but is name of inside template name if fourth is false which is default
         * @param array $vars Variables assigned to the template
         * @param bool $isFile The additional parameter which is related to first parameter
         * @return void
         */
        public static function template(string $tpl, array $vars = [], bool $isFile = false)
        {
//            Response::getInstance()->clean();
            $isFile or $tpl = SR_PATH_FRAMEWORK . "include/template/{$tpl}.php";
            if (!is_file($tpl)) {
                $vars['error'] = "'{$tpl}' not found";
                $tpl = SR_PATH_FRAMEWORK . 'include/template/error.php';
            }
            $vars and extract($vars, EXTR_OVERWRITE);
            include $tpl;
        }

    }


}
