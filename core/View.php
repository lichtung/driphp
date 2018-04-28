<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 11:17
 */
declare(strict_types=1);


namespace sharin\core;

use Twig_Function;
use Twig_Loader_Filesystem;
use Twig_Environment;
use sharin\SharinException;


/**
 * Class View
 * @package sharin\core
 */
class View
{

    private $_className = '';
    private $_controller = '';
    private $_module = '';


    public function __construct(string $className = '')
    {
        $this->_className = $className ?: self::getPrevious('class', 2);
        # fetch module and controller name
        list($this->_module, $this->_controller) = self::fetchModuleAndControllerFromControllerName($this->_className);
    }


    private function twigEnvironment(string $theme): Twig_Environment
    {
        # Loaders are responsible for loading templates from a resource such as the file system.
        # Twig_Loader_Filesystem loads templates from the file system.
        # This loader can find templates in folders on the file system and is the preferred way to load them:
        $loader = new Twig_Loader_Filesystem(SR_PATH_PROJECT . "view/{$theme}/{$this->_module}/");

        # Instances of Twig_Environment are used to store the configuration and extensions,
        # and are used to load templates from the file system or other locations.
        $twig = new Twig_Environment($loader, array(
            # When set to true, the generated templates have a __toString() method that you can use to display the
            # generated nodes (default to false).
            'debug' => false,
            # When developing with Twig, it's useful to recompile the template whenever the source code changes.
            # If you don't provide a value for the auto_reload option, it will be determined automatically based on the debug value.
            'auto_reload' => true,
            # If set to false, Twig will silently ignore invalid variables (variables and or attributes/methods that do not exist)
            # and replace them with a null value. When set to true, Twig throws an exception instead (default to false).
            'strict_variables' => false,
//            'charset' => 'utf-8', # The charset used by the templates.
//            'base_template_class' => 'Twig_Template', # The base template class to use for generated templates.
            # An absolute path where to store the compiled templates, or false to disable caching (which is the default).
            # Dripex :Building cache will load more files.
            'cache' => SR_PATH_RUNTIME . "view/{$theme}-{$this->_controller}/",
            # ???
            # Sets the default auto-escaping strategy (name, html, js, css, url, html_attr, or a PHP callback that takes
            # the template "filename" and returns the escaping strategy to use -- the callback cannot be a function name
            # to avoid collision with built-in escaping strategies); set it to false to disable auto-escaping.
            # The name escaping strategy determines the escaping strategy to use for a template based on the template
            # filename extension (this strategy does not incur any overhead at runtime as auto-escaping is done at compilation time.)
//            'autoescape' => '',
            #  A flag that indicates which optimizations to apply (default to -1 -- all optimizations are enabled; set it to 0 to disable).
            'optimizations' => -1,
        ));
        return $twig;
    }

    private $_functions = [];

    protected function registerFunction(string $functionName, callable $callable)
    {
        $this->_functions[$functionName] = $callable;
        return $this;
    }

    /**
     * @param array $vars An array of parameters to pass to the template
     * @param string $template The template name,default using the method name
     * @param string $theme template theme
     */
    public function render(array $vars = [], string $template = '', string $theme = 'default')
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        try {
            if ('' === $template) {
                # fetch method name who call the render method
                $template = self::getPrevious();
            }
            $twig = $this->twigEnvironment($theme);
            foreach ($this->_functions as $name => $callable) {
                $twig->addFunction(new Twig_Function($name, $callable));
            }
            echo $twig->render($this->_controller . '/' . $template . '.twig', $vars);
        } catch (\Throwable $e) {
            SharinException::dispose($e);
        }
        exit(0);
    }


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