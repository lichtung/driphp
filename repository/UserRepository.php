<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/21
 * Time: 18:52
 */

namespace driphp\repository;

use driphp\model\UserModel;

/**
 * Class UserRepository
 * @method UserRepository getInstance() static
 * @package driphp\repository
 */
class UserRepository extends Repository
{
    public function modelName(): string
    {
        return UserModel::class;
    }

    /**
     * @param string $name
     * @return UserModel
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\GeneralException
     * @throws \driphp\throws\database\NotFoundException
     * @throws \driphp\throws\database\QueryException
     */
    public function findByName(string $name): UserModel
    {
        $className = $this->modelName();
        /** @var UserModel $model */
        $model = new $className($this->dao);
        return $model->query()->where(['username' => $name])->fetch();
    }

    /**
     * @param string $email
     * @return UserModel
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\GeneralException
     * @throws \driphp\throws\database\NotFoundException
     * @throws \driphp\throws\database\QueryException
     */
    public function findByEmail(string $email): UserModel
    {
        $className = $this->modelName();
        /** @var UserModel $model */
        $model = new $className($this->dao);
        return $model->query()->where(['email' => $email])->fetch();
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $nickname
     * @param string $avatar
     * @return UserModel
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\ExecuteException
     * @throws \driphp\throws\database\GeneralException
     * @throws \driphp\throws\database\NotFoundException
     * @throws \driphp\throws\database\QueryException
     * @throws \driphp\throws\database\ValidateException
     * @throws \driphp\throws\database\exec\DuplicateException
     * @throws \driphp\throws\project\PasswordException
     */
    public function add(string $username, string $password, string $email, string $nickname, string $avatar = ''): UserModel
    {
        $className = $this->modelName();
        /** @var UserModel $model */
        $model = new $className($this->dao);
        $model->username = $username;
        $model->email = $email;
        $model->nickname = $nickname;
        $avatar and $model->avatar = $avatar;
        $model->password = UserModel::encryptPassword($username, $password);
        /** @var UserModel $model */
        $model = $model->insert();
        return $model;
    }

    public function validate()
    {

    }

}