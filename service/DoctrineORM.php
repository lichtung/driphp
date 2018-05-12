<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 08:49
 */
declare(strict_types=1);


namespace sharin\service;


use sharin\core\Service;

/**
 * Class DoctrineORM
 *
 * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
 *
 * @package sharin\service
 */
class DoctrineORM extends Service
{

    protected $config = [
        'entity_home' => SR_PATH_PROJECT . '/doctrine/entities', # /path/to/entity-files
        'connections' => [
            [
                'driver' => 'pdo_mysql',
                'user' => 'root',
                'password' => '123456',
                'name' => 'test',
                'port' => 3306,
            ],
        ],
    ];
}