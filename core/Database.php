<?php
/**
 * Created by PhpStorm.
 * User: v.linzh
 * Date: 2018/4/18
 * Time: 10:53
 */

namespace sharin\core;


use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use sharin\Component;
use sharin\core\database\Dao;
use sharin\throws\core\database\GeneralException;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class Database
 * @method Database getInstance() static
 * @package sharin\core
 */
class Database extends Component
{
    /**
     * @return $this|void
     * @throws GeneralException
     * @throws \sharin\throws\core\ClassNotFoundException
     * @throws \sharin\throws\core\DriverNotDefinedException
     * @throws \sharin\throws\core\database\ConnectException
     */
    protected function initialize()
    {
        $this->configuration = Setup::createAnnotationMetadataConfiguration($this->config['paths'], SR_DEBUG_ON);
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

    protected $config = [
        'paths' => [
            SR_PATH_PROJECT . 'entity/',
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