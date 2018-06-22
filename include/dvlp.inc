<?php
/**
 * User: linzhv@qq.com
 * Date: 15/01/2018
 * Time: 13:29
 */
declare(strict_types=1);

use driphp\library\Trace;
# php版本需要
const DRI_RELY_PHP_VERSION = 7.0;

version_compare(PHP_VERSION, (string)DRI_RELY_PHP_VERSION, '<') and die('require php >= ' . DRI_RELY_PHP_VERSION . '!');


function in_build_message($params, $traces)
{
    $color = '#';
    $str = '9ABCDEF';//随机浅色背景
    for ($i = 0; $i < 6; $i++) $color = $color . $str[rand(0, strlen($str) - 1)];
    $str = "<pre style='background: {$color};width: 100%;padding: 10px;margin: 0'><h3 style='color: midnightblue'><b>F:</b>{$traces[0]['file']} << <b>L:</b>{$traces[0]['line']} >> </h3>";
    foreach ($params as $key => $val) {
        $txt = htmlspecialchars(var_export($val, true));
        $str .= "<b>Parameter- . $key :</b><br /> $txt <br />";
    }
    return $str . '</pre>';
}

function in_build_message_for_cli($params, $traces)
{
    $str = "F:{$traces[0]['file']} << L:{$traces[0]['line']} >>" . PHP_EOL;
    foreach ($params as $key => $val) $str .= "[Parameter-{$key}]\n" . var_export($val, true) . PHP_EOL;
    return $str;
}


/**
 * @param array ...$params
 */
function in_dump(...$params)
{
    echo call_user_func_array(DRI_IS_CLI ? 'in_build_message_for_cli' : 'in_build_message', [
        $params, debug_backtrace()
    ]);
}

/**
 * @param array ...$params
 */
function dumpout(...$params)
{
    exit(call_user_func_array(DRI_IS_CLI ? 'in_build_message_for_cli' : 'in_build_message', [
        $params, debug_backtrace()
    ]));
}

class _inframe_dvlp
{

    private static $showTrace = DRI_DEBUG_ON;

    /**
     * @var array
     */
    private static $highlightes = [];
    /**
     * @var array 状态
     */
    private static $_status = [
        'begin' => [
            DRI_MICROTIME,
            DRI_MEMORY,
        ],
    ];
    /**
     * @var array
     */
    private static $_traces = [];

    /**
     * 强制开启trace
     * @return void
     */
    public static function openTrace()
    {
        self::$showTrace = true;
    }

    /**
     * 强制关闭trace
     * @return void
     */
    public static function closeTrace()
    {
        self::$showTrace = false;
    }

    /**
     * record the runtime's time and memory usage
     * @param string $tag tag of runtime point
     * @return void
     */
    public static function status($tag)
    {
        DRI_DEBUG_ON and self::$_status[$tag] = [
            microtime(true),
            memory_get_usage(),
        ];
    }

    /**
     * import status
     * @param string $tag
     * @param array $status
     */
    public static function import($tag, array $status)
    {
        self::$_status[$tag] = $status;
    }

    /**
     * 记录下跟踪信息
     * @param mixed|null $message 跟踪的信息
     * @return void
     */
    public static function trace($message = null)
    {
        static $index = 0;
        if (!DRI_DEBUG_ON) return;
        if (null === $message) {
            !DRI_IS_CLI and self::$showTrace and Trace::show(self::$highlightes, self::$_traces, self::$_status);
        } else {
            $location = debug_backtrace();
            if (isset($location[0])) {
                $location = "{$location[0]['file']}:{$location[0]['line']}";
            } else {
                $location = $index++;
            }
            if (!is_string($message)) $message = var_export($message, true);
            //同一个位置可能trace多次
            if (isset(self::$_traces[$location])) {
                $index++;
                $location = "$location ({$index})";
            }
            self::$_traces[$location] = $message;
        }
    }
}