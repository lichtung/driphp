<?php
/**
 * User: linzhv@qq.com
 * Date: 09/04/2018
 * Time: 23:13
 *|~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
 *|~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
 *|                 ┏━┓    ┏━┓                  |
 *|                ┏┛ ┻━━━━┛ ┻━┓                |
 *|                ┃           ┃                |　
 *|   ┏┓      ┏┓   ┃           ┃                |
 *|  ┏┛┻━━━━━━┛┻━┓ ┃ ==    ==  ┃                |
 *|  ┃           ┃ ┃           ┃   ┏┓      ┏┓   |
 *|  ┃ ==    ==  ┃ ┃     ^     ┃  ┏┛┻━━━━━━┛┻━┓ |
 *|  ┃           ┃ ┃           ┃  ┃ ==    ==  ┃ |
 *|  ┃     ^     ┃ ┗━━┓      ┏━┛  ┃     ^     ┃ |
 *|  ┗━━┓      ┏━┛    ┃      ┃    ┗━━┓      ┏━┛ |
 *|~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
 *|~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~|
 * Description: Sharing the driphp framework for web developers of beginner.
 */
declare(strict_types=1);

namespace {

    use driphp\Kernel;

    const DRI_VERSION = '0.0';

    define('DRI_MICROTIME', ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))); # 当前时间（微妙）
    define('DRI_MEMORY', memory_get_usage());# byte

    defined('DRI_DEBUG_ON') or define('DRI_DEBUG_ON', true); #  debug模式默认开启
    defined('DRI_LOAN_BALANCE_ON') or define('DRI_LOAN_BALANCE_ON', false);# 负载均衡模式默认关闭（开启时候需要手动设置HOST名称）
    defined('DRI_PROJECT_NAME') or define('DRI_PROJECT_NAME', '');# 项目名称（项目所在目录的名称，如 idea.driphp.com/ ）

    # environment constant
    const DRI_IS_CLI = PHP_SAPI === 'cli'; # 是否是cli模式
    define('DRI_IS_WIN', false !== stripos(PHP_OS, 'WIN'));# 是否是windows系统
    define('DRI_IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'); # 是否是ajax请求，cli模式下始终为false
    # request method
    define('DRI_REQUEST_METHOD', strtoupper($_SERVER['X-HTTP-METHOD-OVERRIDE'] ?? $_SERVER['REQUEST_METHOD'] ?? ''));//'GET', 'DELETE'，'POST'，'PUT' 'PATCH' ...

    # 目录常量
    define('DRI_PATH_ROOT', dirname(__DIR__) . '/'); # 框架所在的上级目录（即根目录）
    const DRI_PATH_FRAMEWORK = __DIR__ . '/';    # 框架目录
    const DRI_PATH_PROJECT = DRI_PATH_ROOT . DRI_PROJECT_NAME . '/'; # 项目目录
    const DRI_PATH_CONFIG = DRI_PATH_PROJECT . 'config/'; # 配置目录
    const DRI_PATH_DATA = DRI_PATH_PROJECT . 'data/'; # 数据目录
    const DRI_PATH_VENDOR = DRI_PATH_PROJECT . 'vendor/'; # 第三方目录
    const DRI_PATH_RUNTIME = DRI_PATH_PROJECT . 'runtime/'; # 运行时目录
    const DRI_PATH_PUBLIC = DRI_PATH_PROJECT . 'public/'; # 公共资源 (css, js, image...) 或者公开脚本
    const DRI_PATH_CONTROLLER = DRI_PATH_PROJECT . 'controller/'; # 控制器目录
    const DRI_PATH_MODEL = DRI_PATH_PROJECT . 'model/'; # 模型目录
    const DRI_PATH_VIEW = DRI_PATH_PROJECT . 'view/'; # 模板文件目录

    # 编码
    const DRI_CHARSET_UTF8 = 'UTF-8';
    const DRI_CHARSET_GBK = 'GBK';
    const DRI_CHARSET_ASCII = 'ASCII';
    const DRI_CHARSET_GB2312 = 'GB2312';
    const DRI_CHARSET_LATIN1 = 'ISO-8859-1';# Latin1 is the alia of ISO-8859-1  欧洲部分国家使用(西欧语言)

    # 类型常量
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
            Kernel::status('shutdown');
            isset($_GET['show_trace']) and require(__DIR__ . '/include/trace.php'); # show trace
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

    use driphp\core\Dispatcher;
    use driphp\core\Logger;
    use driphp\core\Request;
    use driphp\core\response\JSON;
    use driphp\core\Route;
    use driphp\throws\ClassNotFoundException;
    use driphp\throws\ConfigInvalidException;
    use driphp\throws\ConfigNotFoundException;
    use driphp\throws\DriverNotFoundException;
    use driphp\throws\io\FileWriteException;
    use Throwable;

    /**
     * Class KernelException 核心异常,区别于业务异常
     * @package driphp
     */
    abstract class KernelException extends \Exception
    {
        /**
         * DriException constructor.
         * @param object|string|int|float $message
         * @param int $code 错误代号,默认-1表示未知错误
         */
        public function __construct($message, int $code = -1)
        {
            if (!is_string($message)) { # 非字符串，先进行格式化
                switch (gettype($message)) {
                    case DRI_TYPE_ARRAY:
                        $message = var_export($message, true);
                        break;
                    default:
                        $message = (string)$message;
                }
            }
            parent::__construct($message, $code);
        }

        /**
         * @return void
         * @throws \ReflectionException|KernelException
         */
        public static function throwing()
        {
            $instance = (new \ReflectionClass(static::class))->newInstanceArgs(func_get_args());
            throw new $instance;
        }

        /**
         * 处理异常（记录日志、输出展示错误信息）
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
                # 命令行模式下直接输出
                var_dump($information);
            } else {
                if (DRI_IS_AJAX) {
                    # ajax下输出json
                    exit(new JSON($information));
                } else {
                    # 展示错误模板
                    if (DRI_DEBUG_ON) {
                        require_once __DIR__ . '/include/error.php';
                        _display_error($message, $className, $file, $line, $code, $traces);
                    } else {
                        Kernel::template('404');
                    }
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
        protected $index = 'default';
        /** @var string 驱动类名称 */
        protected $driverName = '';
        /** @var array 驱动类配置 */
        protected $driverConfig = [];
        /** @var DriverInterface $driver 驱动实例 */
        protected $driver = null;

        /**
         * 获取自身实例
         * 根据传入得配置数组得不同得到不同得实例
         * @param array $config
         * @return Component
         * @throws
         */
        final public static function factory(array $config = []): Component
        {
            static $_instances = [];
            $key = md5(static::class . Kernel::hash($config));
            if (!isset($_instances[$key])) {
                $_config = Kernel::getInstance()->config(static::class);
                $_config = $_config ? array_merge($_config, $config) : $config;
                $_instances[$key] = new static($_config); # Kernel::factory($className, [$_config]);
            }
            return $_instances[$key];
        }

        /**
         * Component constructor.
         * @param array $config
         */
        final protected function __construct(array $config = [])
        {
            $this->config = array_merge($this->config, $config);
            $this->initialize();
        }

        /**
         * 初始化
         * @return void
         */
        abstract protected function initialize();

        /**
         * 获取驱动信息
         * @return array [驱动索引、驱动类、驱动配置]
         */
        final public function driveInfo(): array
        {
            return [$this->index, $this->driverName, $this->driverConfig];
        }


        /**
         * 获取驱动实例
         * @param string $index 驱动器角标
         * @return DriverInterface  返回驱动实例
         * @throws DriverNotFoundException 适配器未定义
         * @throws ClassNotFoundException  适配器类不存在
         */
        public function drive(string $index = '')
        {
            $index and $this->index = $index;
            if (!isset($this->driver)) {
                if (isset($this->config['drivers'][$this->index])) {
                    $this->driverName = $this->config['drivers'][$this->index]['name'];
                    $this->driverConfig = $this->config['drivers'][$this->index]['config'] ?? [];
                    $this->driver = Kernel::factory($this->driverName, [$this->driverConfig, $this]);
                } else {
                    throw new DriverNotFoundException("driver '{$this->index}' not found");
                }
            }
            return $this->driver;
        }

        /**
         * 获取和设置配置项
         * @param string $key 配置项,多级配置项以点号分隔
         * @return array|int|string|bool
         */
        public function config(string $key = '')
        {
            if (empty($key)) {
                return $this->config;
            } else {
                # 设置配置项
                return $this->config[$key] ?? null;
            }
        }

        /**
         * @param string $key
         * @param $value
         * @return bool 返回是否设置成功
         */
        public function setConfig(string $key, $value): bool
        {
            if (strpos($key, '.') !== false) {
                $config = &$this->config;
                foreach (explode('.', $key) as $k) {
                    if ($k) {
                        $config = &$config[$k];
                    } else {
                        return false;
                    }
                }
                $config = $value;
            } else {
                $this->config[$key] = $value;
            }
            return true;
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
            return call_user_func_array([static::factory(), $name], $arguments);
        }

        /**
         * 获取前面操作的方法
         * @param string $item
         * @param int $place
         * @return string
         */
        final public static function getPrevious(string $item = 'function', int $place = 2): string
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            return $trace[$place][$item] ?? '';
        }

    }

    /**
     * Class Kernel 核心引擎
     * @package driphp
     */
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
            Route::class => [], # 路由配置
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
                KernelException::dispose(null, $code, $message, $file, $line);
            });
            set_exception_handler(function (Throwable $e) {
                KernelException::dispose($e);
            });

            Kernel::status('init_end');
            return $this;
        }

        /**
         * 设定路由
         * @param array $route
         * @return Kernel
         */
        public function route(array $route): Kernel
        {
            $this->config[Route::class] = array_merge($this->config[Route::class], $route);
            return $this;
        }

        /**
         * 运行应用
         * @return void
         * @throws
         */
        public function start()
        {
            if (!DRI_IS_CLI) {
                self::status('start');
                $request = Request::factory();
                self::status('route');
                $route = Route::factory()->parse($request);
                self::status('dispatch');
                Dispatcher::factory()->dispatch($route);
                self::status('end');
            }
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
         * @param array|null $config 组件配置,为空时获取配置，否则设定配置
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
         * @throws ConfigNotFoundException
         * @throws ConfigInvalidException
         */
        public static function readConfig(string $path, $replace = [])
        {
            if (!is_file($path)) throw new ConfigNotFoundException($path);
            if (!is_array($result = is_file($path) ? include($path) : $replace)) {
                throw new ConfigInvalidException("PHP config [$path] return a non-array");
            }
            return $result;
        }

        /**
         * 写入配置
         * @version 1.0
         * @param string $path
         * @param array $config
         * @return void
         * @throws FileWriteException
         */
        public static function writeConfig(string $path, array $config)
        {
            $parentDirectory = dirname($path);
            is_dir($parentDirectory) or mkdir($parentDirectory, 0777, true);
            $content = '<?php defined(\'DRI_VERSION\') or die(\'No Permission\'); return ' . var_export($config, true) . ';';
            if (!file_put_contents($path, $content)) {
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
                $vars['error'] = "'{
                                $tpl}' not found";
                $tpl = DRI_PATH_FRAMEWORK . 'include/template/error.php';
            }
            $vars and extract($vars, EXTR_OVERWRITE);
            include $tpl;
        }

    }


}
