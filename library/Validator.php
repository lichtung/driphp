<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 17:30
 */
declare(strict_types=1);


namespace driphp\library;

/**
 * Class Validator
 * @deprecated
 * @package driphp\library
 */
class Validator
{
    /**
     * 验证是否是邮箱
     * @param string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        $res = preg_match("/^([0-9A-Za-z\\-_\\.]+)@[0-9a-z]+\\.[a-z]{1,6}(\\.[a-z]{2})?$/i", $email, $match);
        return $res === 1;
    }

    public static function isPhone(string $phone): bool
    {
        return preg_match("/^1[34578]\\d{9}$/", $phone) === 1;
    }

    public static function isPasswordInvalid(string $password): string
    {
//        $r4 = '/[~!@#$%^&*()\-_=+{};:<,.>?]/';  // special char
        if (preg_match_all('/[A-Z]/', $password, $o) < 1) {
            return "密码必须包含至少一个大写字母！";
        }
        if (preg_match_all('/[a-z]/', $password, $o) < 1) {
            return "密码必须包含至少一个小写字母！";
        }
        if (preg_match_all('/[0-9]/', $password, $o) < 1) {
            return "密码必须包含至少一个数字！";
        }
        if (strlen($password) < 8) {
            return "密码必须包含至少含有8个字符！";
        }
        return '';
    }

}