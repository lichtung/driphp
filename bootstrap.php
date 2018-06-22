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
 * Description: Sharing the driphp framework for web developers of beginner.
 */
declare(strict_types=1);

namespace {

    use driphp\Kernel;
    const DRI_VERSION = '0.0';

    define('DRI_MICROTIME', ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)));
    define('DRI_MEMORY', memory_get_usage());# byte

    defined('DRI_DEBUG_ON') or define('DRI_DEBUG_ON', true); #  debug模式默认开启
    defined('DRI_LOAN_BALANCE_ON') or define('DRI_LOAN_BALANCE_ON', false);# 负载均衡模式默认关闭（开启时候需要手动设置HOST名称）
    defined('DRI_PROJECT_NAME') or define('DRI_PROJECT_NAME', '');# 项目名称（项目所在目录的名称，如 idea.driphp.com/ ）

    # environment constant
    const DRI_IS_CLI = PHP_SAPI === 'cli'; # is client environment?
    define('DRI_IS_WIN', false !== stripos(PHP_OS, 'WIN'));# is windows?
    define('DRI_IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    # request method
    define('DRI_REQUEST_METHOD', strtoupper($_SERVER['REQUEST_METHOD'] ?? ''));//'GET', 'DELETE'，'POST'，'PUT' 'PATCH' ...

    # directory constant
    define('DRI_PATH_ROOT', dirname(__DIR__) . '/'); # the parent directory of project and framework
    const DRI_PATH_FRAMEWORK = __DIR__ . '/';    # framework directory
    const DRI_PATH_PROJECT = DRI_PATH_ROOT . DRI_PROJECT_NAME . '/'; # project directory
    const DRI_PATH_CONFIG = DRI_PATH_PROJECT . 'config/'; # project directory
    const DRI_PATH_DATA = DRI_PATH_PROJECT . 'data/'; # data directory to store dynamic config or file-based data
    const DRI_PATH_VENDOR = DRI_PATH_PROJECT . 'vendor/'; # vendor directory for project
    const DRI_PATH_RUNTIME = DRI_PATH_PROJECT . 'runtime/'; # to store temporary, cache file
    const DRI_PATH_PUBLIC = DRI_PATH_PROJECT . 'public/'; # public resource (css, js, image...) and entry script
    const DRI_PATH_CONTROLLER = DRI_PATH_PROJECT . 'controller/'; # to store controller class
    const DRI_PATH_MODEL = DRI_PATH_PROJECT . 'model/'; # to store model class
    const DRI_PATH_VIEW = DRI_PATH_PROJECT . 'view/'; # to store view template


    # charset
    const DRI_CHARSET_UTF8 = 'UTF-8';
    const DRI_CHARSET_GBK = 'GBK';
    const DRI_CHARSET_ASCII = 'ASCII';
    const DRI_CHARSET_GB2312 = 'GB2312';
    const DRI_CHARSET_LATIN1 = 'ISO-8859-1';# Latin1 is the alia of ISO-8859-1  欧洲部分国家使用(西欧语言)

    const DRI_TYPE_BOOL = 'boolean';
    const DRI_TYPE_INT = 'integer';
    const DRI_TYPE_FLOAT = 'double'; # gettype(1.7) === 'double'
    const DRI_TYPE_STR = 'string';
    const DRI_TYPE_ARRAY = 'array';
    const DRI_TYPE_OBJ = 'object'; # gettype(function (){})
    const DRI_TYPE_RESOURCE = 'resource';
    const DRI_TYPE_NULL = 'NULL'; # gettype(null) === 'NULL'
    const DRI_TYPE_UNKNOWN = 'unknown type';

    if (DRI_DEBUG_ON) {
        require __DIR__ . '/include/debug.php';
        DRI_IS_CLI or register_shutdown_function(function () {
//            if (class_exists(Response::class)) echo Response::getInstance();
            Kernel::status('shutdown');
            isset($_GET['show_trace']) and require(__DIR__ . '/include/trace.php');
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

namespace driphp {

    use driphp\core\database\driver\Driver;
    use driphp\core\Dispatcher;
    use driphp\core\Logger;
    use driphp\core\Request;
    use driphp\core\response\JSON;
    use driphp\core\Route;
    use driphp\throws\core\ConfigException;
    use driphp\throws\core\DriverNotDefinedException;
    use driphp\throws\io\FileWriteException;
    use Throwable;
    use driphp\throws\core\ClassNotFoundException;

    /**
     * Class DriException 内置异常
     * @package driphp
     */
    abstract class DriException extends \Exception
    {
        /**
         * DriException constructor.
         * @param object|string|int|float $message
         */
        public function __construct($message)
        {
            if (!is_string($message)) {
                switch (gettype($message)) {
                    case DRI_TYPE_ARRAY:
                        $message = var_export($message, true);
                        break;
                    default:
                        $message = (string)$message;
                }
            }
            parent::__construct($message, $this->getExceptionCode());
        }

        /**
         * 返回异常的错误代号
         * @return int
         */
        abstract public function getExceptionCode(): int;

        /**
         * @return void
         */
        public static function throwing()
        {
            $instance = (new \ReflectionClass(static::class))->newInstanceArgs(func_get_args());
            throw new $instance;
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
            Logger::getLogger('throwable')->critical($information = [
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'class' => $className,
            ]);
            if (DRI_IS_CLI) {
                var_dump($information);
            } elseif (DRI_IS_AJAX) {
                exit(new JSON($information));
            } else {
                if (DRI_DEBUG_ON) {
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
     * Interface DriverInterface 驱动器器接口
     * @package driphp
     */
    interface DriverInterface
    {
        /**
         * DriverInterface constructor.
         * @param array $config 初始化配置
         * @param Component $context 驱动依附的组件类作为其上下文环境
         */
        public function __construct(array $config, Component $context);
    }

    /**
     * Interface ErrorHandlerInterface 错误处理函数接口
     * @package driphp
     */
    interface ErrorHandlerInterface
    {
        public function handle(int $code, string $message, string $file, int $line);
    }

    interface ExceptionHandlerInterface
    {
        public function handle(Throwable $e);
    }

    /**
     * Class Component 组件类
     * 特性：
     *  - 自动从项目配置中加载组件配置
     *  - 驱动模式设计
     *  - 魔术配置
     * @package driphp
     */
    abstract class Component
    {
        /** @var array $config 组件实例配置 */
        protected $config = [];
        /** @var string $index 默认驱动索引 */
        protected $index = '';
        /** @var string 驱动类名称 */
        protected $driverName = '';
        /** @var array 驱动类配置 */
        protected $driverConfig = [];
        /** @var DriverInterface $driver 驱动实例 */
        protected $driver = null;

        /**
         * 获取实例
         * @param array $config
         * @return Component
         */
        final public static function getInstance(array $config = []): Component
        {
            $className = static::class;
            $_config = Kernel::getInstance()->config($className);

            if ($config) $_config = array_merge($_config, $config);

            try {
                /** @var Component $component */
                $component = Kernel::factory($className, [$_config]);
            } catch (ClassNotFoundException $throwable) {
                # 不会发生
            }
            return $component;
        }

        /**
         * Component constructor.
         * @param array $config
         */
        final public function __construct(array $config = [])
        {
            $this->config = array_merge($this->config, $config);
            $this->initialize();
        }

        /**
         * 初始化
         * @return $this
         */
        abstract protected function initialize();

        /**
         * 获取驱动索引
         * @return array
         */
        public function driveInfo(): array
        {
            return [
                $this->index,
                $this->driverName,
                $this->driverConfig,
            ];
        }


        /**
         * 加载驱动
         * @param string $index 驱动器角标
         * @return Driver |object 返回驱动实例
         * @throws DriverNotDefinedException 适配器未定义
         * @throws ClassNotFoundException  适配器类不存在
         */
        public function drive(string $index = 'default'): DriverInterface
        {
            $this->index = $index;
            if (!isset($this->driver)) {
                if (isset($this->config['drivers'][$this->index])) {
                    $this->driverName = $this->config['drivers'][$this->index]['name'];
                    $this->driverConfig = $this->config['drivers'][$this->index]['config'] ?? [];
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
         * 获取和设置配置项
         * @param string $key 配置项,多级配置项以点号分隔
         * @param mixed|null $value 为null时表示获取配置值,否则标识获取配置值
         * @return mixed|null
         * @throws ConfigException 访问的config不存在时抛出
         */
        public function config(string $key, $value = null)
        {
            if (isset($value)) {
                if (strpos($key, '.') !== false) {
                    $config = &$this->config;
                    foreach (explode('.', $key) as $k) {
                        if ($k) {
//                            if (!isset($config[$k])) $config[$k] = [];
                            $config = &$config[$k];
                        } else {
                            throw new ConfigException($key);
                        }
                    }
                    $config = $value;
                } else {
                    $this->config[$key] = $value;
                }
            } else {
                # 设置配置项
                $value = $this->config[$key] ?? null;
            }
            return $value;
        }

        /**
         * 设置配置项
         * @param string $name 配置项名称
         * @param mixed $value 配置值
         * @return void
         */
        public function __set(string $name, $value)
        {
            $this->config[$name] = $value;
        }

        /**
         * 获取配置项
         * @param string $name 配置项名称
         * @return mixed 返回配置值,配置项不存在返回null
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
            'session.save_path' => DRI_PATH_RUNTIME,# tcp://127.0.0.1:6379
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
                $path = (strpos($className, 'driphp\\') === 0) ? DRI_PATH_ROOT : DRI_PATH_PROJECT;
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
                DriException::dispose(null, $code, $message, $file, $line);
            });
            set_exception_handler(function (Throwable $e) {
                DriException::dispose($e);
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
            if (DRI_IS_CLI) return;
            self::status('start');
            $request = Request::getInstance();
            self::status('route');
            $route = Route::getInstance()->parse($request);
            self::status('dispatch');
            Dispatcher::getInstance()->dispatch($route);
            self::status('end');
        }

        /**
         * 获取/设置组件的项目配置
         *
         * 每个组件都有一个默认的配置数组，存在于组件类的config属性中，姑且可以称之为"约定"
         * 而项目配置会逐项覆盖组件的约定项目，使得开发者可以自行定义项目需要的配置
         *
         *
         * 1、当需要对组件进行配置时，将参数而设置为配置数组，就可以按项覆盖预设的项目配置
         * ```
         * Kernel::getInstance()->>config( MySQL::class, [...] );
         * ```
         * 2、获取组件配置
         * ```
         * Kernel::getInstance()->>config( MySQL::class);
         * ```
         * @example
         *
         * @version 1.0
         * @param string $component 组件名称
         * @param array|null $config 组件配置
         * @return array
         */
        public function config(string $component, array $config = null): array
        {
            if (isset($config)) {
                if (!empty($this->config[$component])) {
                    $config = array_merge($this->config[$component], $config);
                }
                $this->config[$component] = $config;
            } else {
                if (!isset($this->config[$component])) {
                    if (is_file($extra = DRI_PATH_CONFIG . str_replace('\\', '.', $component) . '.php')) {
                        $this->config[$component] = include $extra;
                    } else {
                        $this->config[$component] = [];
                    }
                }
            }
            return $this->config[$component];
        }

        ######################################### 静态方法区 #############################################################

        /**
         * @return Kernel
         */
        public static function getInstance(): Kernel
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
                    DRI_MICROTIME,
                    DRI_MEMORY,
                ],
            ];
            return isset($tag) ? ($_status[$tag] = [microtime(true), memory_get_usage()]) : $_status;
        }

        /**
         * 读取配置
         * @version 1.0
         * @param string $path
         * @param array|mixed $replace
         * @return array|mixed
         * @throws ConfigException
         */
        public static function readConfig(string $path, $replace = [])
        {
            if (!is_array($result = is_file($path) ? include($path) : $replace)) {
                throw new ConfigException("PHP config [$path] return a non-array");
            };
            return $result;
        }

        /**
         * 写入配置
         * @version 1.0
         * @param string $path
         * @param array $config
         * @throws FileWriteException
         */
        public static function writeConfig(string $path, array $config): void
        {
            $parentDirectory = dirname($path);
            is_dir($parentDirectory) or mkdir($parentDirectory, 0777, true);
            if (!file_put_contents($path, '<?php defined(\'DRI_VERSION\') or die(\'No Permission\'); return ' .
                var_export($config, true) . ';')) {
                throw new FileWriteException($path);
            }
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
                case DRI_TYPE_ARRAY:
                    foreach ($params as $item) {
                        $hash .= self::hash($item);
                    }
                    break;
                case DRI_TYPE_OBJ:
                    $hash = spl_object_hash($params);
                    break;
                case DRI_TYPE_RESOURCE:
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
            $isFile or $tpl = DRI_PATH_FRAMEWORK . "include/template/{$tpl}.php";
            if (!is_file($tpl)) {
                $vars['error'] = "'{$tpl}' not found";
                $tpl = DRI_PATH_FRAMEWORK . 'include/template/error.php';
            }
            $vars and extract($vars, EXTR_OVERWRITE);
            include $tpl;
        }

    }


}
