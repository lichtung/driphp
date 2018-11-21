<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/21
 * Time: 18:53
 */

namespace driphp\repository;


use driphp\database\Dao;
use driphp\database\ORM;
use driphp\library\traits\Singleton;

abstract class Repository
{
    use Singleton;
    /** @var Dao */
    protected $dao;

    /**
     * Repository constructor.
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     */
    public function __construct()
    {
        $this->dao = Dao::connect();
    }

    /**
     * @return string
     */
    abstract public function modelName(): string;

    /**
     * @param int $id
     * @return ORM
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\NotFoundException
     * @throws \driphp\throws\database\QueryException
     */
    public function find(int $id)
    {
        $className = $this->modelName();
        /** @var ORM $model */
        $model = new $className($this->dao);
        return $model->find($id);
    }
}