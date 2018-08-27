<?php
/**
 * Created by PhpStorm.
 * User: v.linzh
 * Date: 2018/4/18
 * Time: 10:53
 */

namespace driphp\core;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use driphp\core\database\Dao;
use driphp\throws\database\GeneralException;

/**
 * Class Doctrine
 * 使用doctrine作为数据库高层应用层
 * @method Doctrine getInstance() static
 * @package driphp\core
 */
class Doctrine extends Service
{
    protected $config = [
        'paths' => [
            DRI_PATH_PROJECT . 'entity/',
        ],
    ];
    /**
     * @var Connection
     */
    private $connection = null;
    /**
     * @var Configuration
     */
    private $configuration = null;
    /**
     * @var EntityManager
     */
    private $entityManager = null;

    /**
     * @throws GeneralException
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\NoDriverAvailableException
     */
    protected function initialize()
    {
        $this->configuration = Setup::createAnnotationMetadataConfiguration($this->config['paths'], DRI_DEBUG_ON);
        try {
            $this->connection = DriverManager::getConnection([
                'pdo' => Dao::getInstance($this->index)->drive()
            ], $this->configuration, new EventManager());
            $this->entityManager = EntityManager::create($this->connection, $this->configuration);
        } catch (DBALException $e) {
            throw new GeneralException($e->getMessage());
        } catch (ORMException $e) {
            throw new GeneralException($e->getMessage());
        }
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getRepository(string $entityName): EntityRepository
    {
        return $this->entityManager->getRepository($entityName);
    }
}