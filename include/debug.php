<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/14 0014
 * Time: 19:08
 */
declare(strict_types=1);

version_compare(PHP_VERSION, '7.0', '<') and die('require php >= 7.0!');

function sr_build_message($params, $traces)
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

function sr_build_message_for_cli($params, $traces)
{
    $str = "F:{$traces[0]['file']} << L:{$traces[0]['line']} >>" . PHP_EOL;
    foreach ($params as $key => $val) $str .= "[Parameter-{$key}]\n" . var_export($val, true) . PHP_EOL;
    return $str;
}

/**
 * @param array ...$params
 */
function dumpon(...$params)
{
    echo call_user_func_array(SR_IS_CLI ? 'sr_build_message_for_cli' : 'sr_build_message', [
        $params, debug_backtrace()
    ]);
}

/**
 * @param array ...$params
 */
function dumpout(...$params)
{
    exit(call_user_func_array(SR_IS_CLI ? 'sr_build_message_for_cli' : 'sr_build_message', [
        $params, debug_backtrace()
    ]));
}
