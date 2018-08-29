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

class UniTest extends TestCase
{

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $config = Yaml::parse(file_get_contents(__DIR__ . '/../env.yaml'));
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