<?php
/**
 * User: linzhv@qq.com
 * Date: 14/04/2018
 * Time: 18:34
 */
declare(strict_types=1);


namespace sharin\core;

use sharin\throws\core\dispatch\ActionAccessException;
use sharin\throws\core\ClassNotFoundException;
use sharin\throws\core\dispatch\ParameterNotFoundException;
use Throwable;
use ReflectionClass;
use sharin\Kernel;
use sharin\throws\core\dispatch\ActionNotFoundException;
use sharin\throws\core\dispatch\ControllerNotFoundException;
use sharin\throws\core\dispatch\ModulesNotFoundException;

class Dispatcher
{
    /**
     * @param Request $request
     * @return bool
     * @throws ActionAccessException 方法禁止访问
     * @throws ActionNotFoundException
     * @throws ControllerNotFoundException
     * @throws ModulesNotFoundException
     * @throws ParameterNotFoundException
     */
    public static function dispatch(Request $request): bool
    {
        $requestModules = $request->getModule();
        if (!is_dir($modulePath = SR_PATH_PROJECT . 'controller/' . $requestModules)) throw new ModulesNotFoundException($modulePath);
        if (!class_exists($controllerName = 'controller\\' . ($requestModules ? $requestModules . '\\' : '') . ucfirst($request->getController()), true)) throw new ControllerNotFoundException($controllerName);
        try {
            self::runMethod($controllerName, $request->getAction(), $_REQUEST);
        } catch (ActionNotFoundException $e1) {
            try {
                self::runMethod($controllerName, '_empty', $_REQUEST);
            } catch (ActionNotFoundException $e2) {
                throw $e1;
            }
        }
        return true;
    }

    /**
     * @param string $controllerName
     * @param string $actionName
     * @param array $arguments
     * @return Response
     * @throws ActionAccessException
     * @throws ActionNotFoundException
     * @throws ControllerNotFoundException
     * @throws ParameterNotFoundException
     */
    public static function runMethod(string $controllerName, string $actionName, array $arguments): Response
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

            if (!$method->isPublic() or $method->isStatic()) {
                throw new ActionAccessException($method->name);
            }
            $controller = Kernel::factory($controllerName);

            if ($method->getNumberOfParameters()) {//有参数
                $args = [];
                /** @var \ReflectionParameter[] $methodParams */
                $methodParams = $method->getParameters();
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
        } catch (ClassNotFoundException $e) {
            throw new ControllerNotFoundException($controllerName);
        }
    }
}