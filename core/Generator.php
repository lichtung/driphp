<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 19:29
 */

namespace driphp\core;


class Generator
{
    /**
     * 产生随机字符串
     * @param int $length
     * @param string $characters
     * @return string
     */
    public static function randomString(int $length = 32, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $string = '';
        $len = strlen($characters);
        for ($i = $length; $i > 0; $i--) {
            $string .= $characters[mt_rand(0, $len - 1)] ?? '';
        }
        return $string;
    }

}