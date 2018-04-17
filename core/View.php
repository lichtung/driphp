<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 11:17
 */
declare(strict_types=1);


namespace sharin\core;


use sharin\Component;
use sharin\SharinException;
use sharin\throws\io\FileNotFoundException;

/**
 * Class View
 * TODO:use twig
 * @method View getInstance(string $index = '') static
 * @package sharin\core
 */
class View extends Component
{

    /**
     * @var array
     */
    private $_template_constants = [];

    protected $content = '';

    public function __toString(): string
    {
        return $this->content;
    }

    public function registerTemplateConstant(string $name, string $value)
    {
        $this->_template_constants[$name] = $value;
    }

    /**
     * parse layout content
     * @param string $content Template content
     * @param string $module
     * @param string $theme
     * @return void
     * @throws FileNotFoundException
     */
    private function __parse_layout(string &$content, string $module, string $theme): void
    {
        if (strpos($content, '<!--layout:') !== false) {
            if (preg_match('/\<\!--layout\:([^-]+)--\>/', $content, $match) and !empty($match[1])) {
                $layout = trim($match[1] ?? '', '/');
                if (strpos($layout, '/') === 0) {
                    $layout_file = SR_PATH_PROJECT . $layout . '.php';
                } else {
                    $layout_file = SR_PATH_PROJECT . "view/{$theme}/{$module}/layout/{$layout}.php";
                }
                if (is_file($layout_file)) {
                    $layout_content = file_get_contents($layout_file);
                    $this->__parse_layout($layout_content, $module, $theme);
                    $content = str_replace('<!--layout-content-->', str_replace($match[0], '', $content), $layout_content);
                } else {
                    throw new FileNotFoundException($layout_file);
                }
            }
        }
    }

    /**
     * display template content
     * @param array $vars An array of parameters to pass to the template
     * @param string $template The template name,default using the method name
     * @param string $theme template theme
     * @return void
     */
    public function __construct(array $vars = [], string $template = '', string $theme = 'default')
    {
        parent::__construct();
        try {
            $cache = null;
            if ('' === $template) {
                $template = self::getPrevious();
            }
            list($module, $controller) = self::fetchModuleAndControllerFromControllerName(self::getPrevious('class'));


            # check the compiled template
            $view = SR_PATH_PROJECT . "view/{$theme}/{$module}/{$controller}/{$template}.php";
            $compile_view = SR_PATH_RUNTIME . "view/{$module}-{$controller}/{$template}.{$theme}.php";;
            if (SR_DEBUG_ON or !is_file($compile_view) or (filemtime($view) > filemtime($compile_view))) {
                # compiled template not exist or template has modified
                if (is_file($view)) {
                    $content = file_get_contents($view);
                    $this->__parse_layout($content, $module, $theme);

                    $request = Request::getInstance();
                    $this->_template_constants['__PUBLIC__'] = $request->getPublicUrl();
                    $this->_template_constants['__HOST__'] = $request->getHostUrl();

                    # template constant replace
                    $content = str_replace(array_keys($this->_template_constants), array_values($this->_template_constants), $content);
                    # template variable replace
                    $content = preg_replace('/\{\{(\w[\w\d_]*)\}\}/', '<?php echo \$${1}; ?>', $content);

                    if (!is_dir($parent_dir = dirname($compile_view))) mkdir($parent_dir, 0700, true);
                    file_put_contents($compile_view, $content);
                } else {
                    throw new FileNotFoundException($view);
                }
            }

            ob_start();
            $vars and extract($vars, EXTR_OVERWRITE);
            include $compile_view;
            $this->content = ob_get_clean();
        } catch (\Throwable $throwable) {
            SharinException::dispose($throwable);
        }
    }


    private static function fetchModuleAndControllerFromControllerName(string $className)
    {
        $mc = explode('\\', substr($className, 11));#strlen('controller\\') == 10
        $_controller = array_pop($mc);
        $_module = $mc ? implode('/', $mc) : '';
        return [$_module, $_controller];
    }

    public static function getPrevious(string $item = 'function', int $place = 2): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        return $trace[$place][$item] ?? '';
    }
}