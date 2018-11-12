<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:19
 */

namespace driphp\library\traits;

use Closure;
use driphp\throws\MethodNotFoundException;

/**
 * Trait Macroable
 *
 * class Hello
 * {
 *      use MacroableTrait;
 * }
 *
 *  // include '/tmp/test.php';
 *  $hello = new Hello;
 *  Hello::macro('sayHi', function(){
 *      echo "Hello","\n<br>\n";
 *  });
 *  $hello->sayHi();
 *  Hello::sayHi();
 *
 * @package driphp\library\traits
 */
trait Macroable
{

    /**
     * The registered string macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Register a custom macro.
     *
     * @param  string $name
     * @param  callable $macro
     * @return void
     */
    public static function macro($name, callable $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Checks if macro is registered.
     *
     * @param  string $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     * @throws MethodNotFoundException
     */
    public static function __callStatic($method, $parameters)
    {
        if (static::hasMacro($method)) {
            if (static::$macros[$method] instanceof Closure) {
                return call_user_func_array(Closure::bind(static::$macros[$method], null, get_called_class()), $parameters);
            } else {
                return call_user_func_array(static::$macros[$method], $parameters);
            }
        }
        throw new MethodNotFoundException("Method {$method} does not exist.");
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            if (static::$macros[$method] instanceof Closure) {
                /** @var $macro Closure */
                $macro = static::$macros[$method];
                return call_user_func_array($macro->bindTo($this, get_class($this)), $parameters);
            } else {
                return call_user_func_array(static::$macros[$method], $parameters);
            }
        }

        throw new MethodNotFoundException("Method {$method} does not exist.");
    }
}