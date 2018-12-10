<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/30
 * Time: 11:58
 */

namespace controller\manage;


use driphp\core\Chars;
use driphp\core\Cookie;
use driphp\library\encrypt\OpenSSL;
use driphp\repository\UserRepository;
use driphp\throws\database\NotFoundException;
use phpDocumentor\Reflection\Types\This;
use driphp\throws\database\ValidateException;

class Sign extends Base
{
    /**
     * @param string $username
     * @param string $password
     * @param string $token
     * @return \driphp\core\response\View|\driphp\core\response\JSON
     * @throws \driphp\throws\ClassNotFoundException
     * @throws \driphp\throws\DriverNotFoundException
     * @throws \driphp\throws\database\ConnectException
     * @throws \driphp\throws\database\DataInvalidException
     * @throws \driphp\throws\database\GeneralException
     * @throws \driphp\throws\database\QueryException
     *
     * @throws ValidateException token验证失败时抛出
     */
    public function in(string $username = '', string $password = '', string $token = '')
    {
        if ($token) {
            try {
                $this->authorizeToken($token);


                return $this->success([
                    'redirect' => '',
                ]);
            } catch (NotFoundException $exception) {
                return $this->fail(DRI_DEBUG_ON ? "用户'$username'不存在" : '账号或密码不正确');
            }
        } else {
            $token = OpenSSL::factory()->encryptInPrivate((string)time()); # 私钥加密
            return $this->render([
                'token' => $token,
            ]);
        }
    }

    private function getUserModelBy(string $identify)
    {
        $pattern = '(?:[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])';
        if (preg_match($pattern, $identify)) {
            $user = UserRepository::getInstance()->findByEmail($identify);
        } else {
            $user = UserRepository::getInstance()->findByName($identify);
        }

    }

    /**
     * @param string $token
     * @return void
     * @throws ValidateException
     */
    private function authorizeToken(string $token)
    {
        $time = OpenSSL::factory()->decryptInPublic($token);
        if (!$time) {
            throw new ValidateException('token不正确');
        }
        if ((int)$time + 3600 < time()) {
            throw new ValidateException('页面过期');
        }
    }
}