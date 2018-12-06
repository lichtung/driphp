<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/21
 * Time: 18:05
 */

namespace {

    use driphp\database\Dao;
    use driphp\database\ORM;
    use driphp\Kernel;
    use driphp\KernelException;
    use Symfony\Component\Yaml\Yaml;

    require __DIR__ . '/../bootstrap.php';
    require __DIR__ . '/../vendor/autoload.php';
    $env = __DIR__ . '/../data/env.yaml';
    if (!is_file($env)) {
        copy(__DIR__ . '/../env.sample.yaml', $env);
    }
    $config = Yaml::parse(file_get_contents($env));
    Kernel::getInstance()->init($config)->start();

    $modelName = ucfirst($argv[1] ?? '');
    if (empty($modelName)) {
        die('Usage: php initorm.php [model_name]');
    }

    $modelName = "\\driphp\\model\\{$modelName}Model";
    try {
        $dao = Dao::connect('right');
        /** @var ORM $user */
        $user = new $modelName($dao);
        if ($user->installed()) {
            $user->uninstall();
        }
        $user->install();
        echo "Install {$user->getTableName()} done, properties is here:\n" . $user->generateDocumentDescription();

    } catch (KernelException $exception) {
        echo $exception;
    }

}