<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/9/6
 * Time: 17:18
 */

namespace {

    use driphp\Kernel;
    use Symfony\Component\Yaml\Yaml;

    define('DRI_DEBUG_ON', is_file(__DIR__ . '/develop.feature'));

    const DRI_PROJECT_NAME = 'driphp';
    require __DIR__ . '/../../driphp/bootstrap.php';

    # 加载配置环境测试
    if (class_exists(Yaml::class)) {
        $env = __DIR__ . '/../tests/env.yaml';
        if (is_file($env)) {
            $config = Yaml::parse(file_get_contents($env));
        }
    }

    Kernel::getInstance()->init($config ?? [])->start();
}