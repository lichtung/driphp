<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 18:41
 */
declare(strict_types=1);


namespace driphp\library\traits;

use driphp\throwable\core\MethodNotFoundException;
use Closure;

/**
 * Trait Macroable
 *
 * Usage:
 * What Does “Macroable” Mean in Laravel
 * To start, let’s add the following to our app/routes.php file.
 *
 * #File: app/routes.php
 * class Hello{}
 * Route::get('testbed', function(){
 *  $hello = new Hello;
 *  $hello->sayHi();
 * });
 * #In real life we’d never define a class in the app/routes.php file, but it’s convenient for demo/learning purposes.
 * #If we load the testbed route in a browser
 * #
 * http://laravel.example.com/testbed
 * We’ll get the following PHP error
 *
 * Call to undefined method Hello::sayHi()
 * This is unsurprising, as we never defined a sayHi method for the Hello class.
 *
 * Next, let’s add the MacroableTrait to our class
 *
 * #File: app/routes.php
 * class Hello
 * {
 *  use Illuminate\Support\Traits\MacroableTrait;
 * }
 * Here we’ve used the full trait name, namespace and all. If you’re not familiar with traits, checkoutlast week’s primer. Traits follow the same namespace rules as classes, and using a trait will invoke the PHP autoloader. If we load the page with the above in place, you’ll still see the same error
 *
 * Call to undefined method Hello::sayHi()
 * However, now try adding the following code to your route
 *
 * #File:
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
 * @package driphp\library
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