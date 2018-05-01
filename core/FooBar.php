<?php
/**
 * User: linzhv@qq.com
 * Date: 28/04/2018
 * Time: 22:54
 */
declare(strict_types=1);


namespace sharin\core;


final class FooBar
{

    public static function fetchModuleAndControllerFromControllerName(string $className)
    {
        $mc = explode('\\', substr($className, 11));#strlen('controller\\') == 10
        $_controller = array_pop($mc);
        $_module = $mc ? implode('/', $mc) : '';
        return [$_module, strtolower($_controller)];
    }

    public static function getPrevious(string $item = 'function', int $place = 2): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        return $trace[$place][$item] ?? '';
    }

}