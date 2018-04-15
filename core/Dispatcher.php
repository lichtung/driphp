<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:34
 */
declare(strict_types=1);


namespace sharin\core;

use sharin\Component;
use sharin\throws\core\dispatch\ActionAccessException;
use sharin\throws\core\ClassNotFoundException;
use sharin\throws\core\dispatch\ParameterNotFoundException;
use sharin\throws\core\dispatch\RouteInvalidException;
use Throwable;
use Closure;
use ReflectionClass;
use sharin\Kernel;
use sharin\throws\core\dispatch\ActionNotFoundException;
use sharin\throws\core\dispatch\ControllerNotFoundException;
use sharin\throws\core\dispatch\ModulesNotFoundException;

/**
 * Class Dispatcher
 * @method Dispatcher getInstance(string $index = '') static
 * @package sharin\core
 */
class Dispatcher extends Component
{


    /**
     * @param $route
     * @return void
     * @throws ActionAccessException
     * @throws ActionNotFoundException
     * @throws ControllerNotFoundException
     * @throws ModulesNotFoundException
     * @throws ParameterNotFoundException
     * @throws RouteInvalidException 错误的路由规则
     */
    public function dispatch($route): void
    {
        if (isset($route)) {
            if (is_array($route)) {
                list($controller, $action) = $route;
                Dispatcher::runMethod($controller, $action, $_REQUEST);
            } elseif (is_string($route)) {
                if (strpos($route, 'http') === 0) {
                    Response::getInstance()->redirect($route); # 立即重定向
                } else {
                    Dispatcher::runMethod($route, 'invoke');
                }
            } elseif ($route instanceof Closure) {
                call_user_func($route);
            } else {
                throw new RouteInvalidException($route);
            }
        } else {
            $request = Request::getInstance();

            $requestModules = $request->getModule();
            if (!is_dir($modulePath = SR_PATH_PROJECT . 'controller/' . $requestModules))
                throw new ModulesNotFoundException($modulePath);
            if (!class_exists($controllerName = 'controller\\' . ($requestModules ? $requestModules . '\\' : '') . ucfirst($request->getController())))
                throw new ControllerNotFoundException($controllerName);
            try {
                self::runMethod($controllerName, $request->getAction(), $_REQUEST);
            } catch (ActionNotFoundException $e1) {
                try {
                    self::runMethod($controllerName, '_empty', $_REQUEST);
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
     * @return Response
     * @throws ActionAccessException
     * @throws ActionNotFoundException
     * @throws ControllerNotFoundException
     * @throws ParameterNotFoundException
     */
    public static function runMethod(string $controllerName, string $actionName, array $arguments = null): Response
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
                throw new ActionAccessException($method->name);
            }
            $controller = Kernel::factory($controllerName);
        } catch (ClassNotFoundException $e) {
            throw new ControllerNotFoundException($controllerName);
        }

        if ($method->getNumberOfParameters()) {//有参数
            $args = [];
            /** @var \ReflectionParameter[] $methodParams */
            $methodParams = $method->getParameters();
            isset($arguments) or $arguments = SR_IS_CLI ? Request::getInstance()->getCommandArguments() : $_REQUEST;
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
        return $result;
    }
}