<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 22:14
 */
declare(strict_types=1);


namespace sharin\service\ss;


/**
 * 输出控制
 * @author Corz
 * Class Trace
 * @package sharin\service\ss
 */
class Trace
{

    /**
     * debug信息
     * @param string $info
     */
    public static function debug($info)
    {
        if (self::isDebug()) {
            echo var_export($info, true) . PHP_EOL;
        }
    }

    /**
     * debug信息
     * @param string $info
     */
    public static function info($info)
    {
        if (defined('DAEMON') and !DAEMON) {
            echo var_export($info, true) . PHP_EOL;
        }
    }

    /**
     *
     */
    protected static function isDebug()
    {
        return defined('DEBUG') ? DEBUG : false;
    }
}