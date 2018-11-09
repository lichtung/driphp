<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:34
 */
declare(strict_types=1);


namespace driphp\core;

use driphp\throws\core\ParameterNotFoundException;
use driphp\throws\RouteException;
use Throwable;
use Closure;
use ReflectionClass;
use driphp\Component;
use driphp\core\response\Redirect;
use driphp\Kernel;
use driphp\throws\ClassNotFoundException;
use driphp\throws\core\ActionNotFoundException;
use driphp\throws\core\ControllerNotFoundException;
use driphp\throws\core\ModulesNotFoundException;

/**
 * Class Dispatcher
 * @method Dispatcher getInstance(string $index = '') static
 * @package driphp\core
 */
class Dispatcher extends Component
{
    protected function initialize()
    {
    }

    /**
     * @param $route
     * @return void
     * @throws ParameterNotFoundException
     * @throws ActionNotFoundException
     * @throws ControllerNotFoundException
     * @throws ModulesNotFoundException
     * @throws RouteException 错误的路由规则
     */
    public function dispatch($route)
    {
        if (isset($route)) {
            # 闭包，返回路由结果
            if ($route instanceof Closure) {
                $route = call_user_func($route);
            }
            switch ($type = gettype($route)) {
                case DRI_TYPE_ARRAY:
                    list($controller, $action) = $route;
                    Dispatcher::runMethod($controller, $action);
                    break;
                case DRI_TYPE_STR:
                    if (strpos($route, 'http') === 0) {
                        exit(new Redirect($route));# 立即重定向
                    } else {
                        Dispatcher::runMethod($route, 'invoke');
                    }
                    break;
                default:
                    throw new RouteException($route);

            }
        } else {
            $request = Request::factory();

            $requestModules = $request->getModule();
            if (!is_dir($modulePath = DRI_PATH_PROJECT . 'controller/' . $requestModules))
                throw new ModulesNotFoundException($modulePath);
            if (!class_exists($controllerName = 'controller\\' . ($requestModules ? str_replace('/', '\\', $requestModules) . '\\' : '') . ucfirst($request->getController())))
                throw new ControllerNotFoundException($controllerName);
            try {
                self::runMethod($controllerName, $request->getAction());
            } catch (ActionNotFoundException $e1) {
                try {
                    self::runMethod($controllerName, 'invoke');
                } catch (ActionNotFoundException $e2) {
                    throw $e1;
                }
            }
        }
    }

    /**
     * @param string $controllerName 控制器类名（全称）
     * @param string $actionName 操作名（方法名）
     * @param array $arguments 参数列表，默认从
     * @return void
     * @throws ActionNotFoundException
     * @throws ControllerNotFoundException
     * @throws ParameterNotFoundException
     */
    public static function runMethod(string $controllerName, string $actionName, array $arguments = null)
    {
        try {
            /** @var ReflectionClass $controller */
            $controller = Kernel::reflect($controllerName);
            try {
                $method = $controller->getMethod($actionName); # A ReflectionException if the method does not exist.
            } catch (Throwable $e) {
                # A ReflectionException if the method does not exist.
                throw new ActionNotFoundException($actionName);
            }

            # 非公开方法、静态方法、以下划线开头的方法都是被禁止访问的
            if (!$method->isPublic() or $method->isStatic() or strpos($method->name, '_') === 0) {
                throw new ActionNotFoundException($method->name);
            }
            $controller = Kernel::factory($controllerName);
        } catch (ClassNotFoundException $e) {
            throw new ControllerNotFoundException($controllerName);
        }

        $mc = explode('\\', substr($controllerName, 11));#strlen('controller\\') == 10

        # 建立请求常量
        Request::factory()->setController(array_pop($mc) ?? '')
            ->setModule($mc ? implode('/', $mc) : '')
            ->setAction($actionName);

        if ($method->getNumberOfParameters()) {//有参数
            $args = [];
            /** @var \ReflectionParameter[] $methodParams */
            $methodParams = $method->getParameters();
            isset($arguments) or $arguments = DRI_IS_CLI ? Request::factory()->getCommandArguments() : $_REQUEST;
            if ($methodParams) {
                foreach ($methodParams as $param) {
                    $paramName = $param->getName();
                    if (isset($arguments[$paramName])) {
                        # filter dangerous input
                        $args[] = Kernel::filter($arguments[$paramName]);
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        throw new ParameterNotFoundException($paramName);
                    }
                }
            }
            $result = $method->invokeArgs($controller, $args);
        } else {
            $result = $method->invoke($controller);
        }
        if (isset($result) and $result instanceof Response) {
            echo $result;
        }
    }
}