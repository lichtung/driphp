<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/14
 * Time: 10:18
 */

namespace driphp\model;

use driphp\throws\project\PasswordException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Class UserModel
 *
 * @property string $username 账号名称
 * @property string $email 电子邮件
 * @property string $password 默认为123456的md5+sha1加密后的值
 * @property string $nickname 昵称
 * @property string $avatar 头像
 * @property int $disable 登录允许:0-禁用 非0-启用
 *
 * @package driphp\model
 */
class UserModel extends Model
{

    public function tableName(): string
    {
        return 'user';
    }

    /**
     * @return array
     */
    public function structure(): array
    {
        return [
            'username' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '账号名称',
                'unique' => true,
            ],
            'email' => [
                'type' => 'varchar(64)',
                'notnull' => true,
                'default' => '',
                'comment' => '电子邮件',
                'charset' => 'ascii',
                'unique' => true,
            ],
            'password' => [
                'type' => 'char(32)',
                'notnull' => true,
                'comment' => '默认为username+123456的10*md5+sha1加密后的值',
                'unique' => true, # 添加唯一限制，防止恶意修改以得到账户的访问权
            ],
            'nickname' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'default' => '',
                'comment' => '昵称',
            ],
            'avatar' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'default' => '/static/manage/img/user2-160x160.jpg',
                'comment' => '头像',
                'charset' => 'ascii',
            ],
        ];
    }

    /**
     * 插入初始化数据
     * @throws PasswordException
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
     */
    protected function onInstalled()
    {
        $this->username = 'root';
        $this->email = '784855684@qq.com';
        $this->nickname = 'linzh';
        $this->password = self::encryptPassword($this->username, '123456');
        $this->insert();
    }

    /**
     * @param string $username
     * @param string $password
     * @return string 每个用户的username不一样，及时设置同样的密码得到的存储在数据库中的密码也是不一样的，代替过去的salt
     * @throws PasswordException
     */
    public static function encryptPassword(string $username, string $password)
    {
        if (empty($username) or empty($password)) {
            throw new PasswordException('username or password should not be empty');
        }
        $password = $username . $password;
        $i = 10;
        while ($i-- > 0) { # 10 次sha1加密
            $password = sha1($password);
        }
        return md5($password);
    }

    protected function validation(): array
    {
        return [
            'username' => [
                new Length(['min' => 4, 'max' => 64])
            ],
            'nickname' => [
                new Length(['min' => 4, 'max' => 64])
            ],
            'email' => [
                new Email(['message' => 'The email \'{{ value }}\' is not a valid email.',])
            ],
        ];
    }

}