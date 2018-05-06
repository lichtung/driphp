<?php
/**
 * User: linzhv@qq.com
 * Date: 05/05/2018
 * Time: 14:18
 */
declare(strict_types=1);


namespace sharin\throws;


use sharin\SharinException;

class DeprecatedException extends SharinException
{

    public function __construct()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $class = $trace[1]['class'] ?? 'UnknownClass';
        $function = $trace[1]['function'] ?? 'unknownFunction';
        $file = $trace[1]['file'] ?? 'unknownFile';
        $line = $trace[1]['line'] ?? 'unknownLine';
        parent::__construct("Method [$class::$function] is deprecated at '$file'($line)", 400);
    }
}