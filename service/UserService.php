<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/21
 * Time: 19:17
 */

namespace driphp\service;


use driphp\repository\UserRepository;
use driphp\throws\PropertyEmptyException;

class UserService extends Service
{

    public function register(string $username, string $password, string $email, string $nickname, string $avatar = '')
    {
        if (empty($username)) {
            throw new PropertyEmptyException('username');
        }
        $repository = UserRepository::getInstance();
        $repository->add($username, $password, $email, $nickname, $avatar);
    }

}