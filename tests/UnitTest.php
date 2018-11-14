<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 12:38
 */
declare(strict_types=1);


namespace driphp\tests;


use driphp\Kernel;
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;

/**
 * Class UnitTest 单元测试类
 * @method void assertTrue($condition, $message = '') static
 * @package driphp\tests
 */
class UnitTest extends TestCase
{

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $env = __DIR__ . '/../data/env.yaml';
        if (!is_file($env)) {
            copy(__DIR__ . '/../env.sample.yaml', $env);
        }
        $config = Yaml::parse(file_get_contents($env));
        foreach ($config as $class => $item) {
            Kernel::getInstance()->config($class, $item);
        }
    }

    protected function config(string $item): array
    {
        return Kernel::getInstance()->config($item);
    }

    public function assertArrayEqual(array $array1, array $array2)
    {
        sort($array1);
        sort($array2);
        $this->assertTrue($array1 === $array2);
    }
}