<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 17:28
 */
declare(strict_types=1);


namespace driphp\library;


class Chars
{

    const RANDOM_ALPHA = 0;
    const RANDOM_NUMBER = 1;
    const RANDOM_UPPERCASE_ALPHA = 2;
    const RANDOM_LOWERCASE_ALPHA = 3;

    /**
     * TODO:为每一个拷贝生成一份不同的62个字符的排序
     */
    const CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * 十进制数转换成62进制
     *
     * @param integer $num
     * @return string
     */
    public static function decto62($num)
    {
        $to = 62;
        $dict = self::CHARS;
        $ret = '';
        do {
            $num = (string)$num;
            $to = (string)$to;
            $ret = $dict[bcmod($num, $to)] . $ret;
            $num = bcdiv($num, $to);
        } while ($num > 0);
        return $ret;
    }

    /**
     * 随机字符串申城
     * @param $length
     * @return string
     */
    public static function randomStr($length)
    {
        $str = self::CHARS;
        $strlen = strlen($str);
        while ($length > $strlen) {
            $str .= $str;
            $strlen += $strlen;
        }
        $str = str_shuffle($str);
        return substr($str, 0, $length);
    }

    /**
     * 62进制数转换成十进制数
     *
     * @param string $num
     * @return string
     */
    public static function decfrom62($num)
    {
        $from = 62;
        $num = strval($num);
        $dict = self::CHARS;
        $len = strlen($num);
        $dec = 0;
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($dict, $num[$i]);
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
        }
        return $dec;
    }

    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $str 需要转换的字符串
     * @param int $start 开始位置
     * @param int $length 截取长度
     * @param string $charset 编码格式
     * @param bool $suffix 截断显示字符
     * @return string
     */
    public static function msubstr(string $str, int $start = 0, int $length, string $charset = 'utf-8', bool $suffix = true): string
    {
        if (function_exists("mb_substr")) {
            $i_str_len = mb_strlen($str);
            $s_sub_str = mb_substr($str, $start, $length, $charset);
            if ($length >= $i_str_len) {
                return $s_sub_str;
            }
            return $s_sub_str . '...';
        } elseif (function_exists('iconv_substr')) {
            return iconv_substr($str, $start, $length, $charset);
        }
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join('', array_slice($match[0], $start, $length));
        if ($suffix) return $slice . '';
        return $slice;
    }

    /**
     * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
     * @param int $len
     * @param int $type 字串类型：0 字母 1 数字 2 大写字母 3 小写字母  4 中文
     * 其他为数字字母混合(去掉了 容易混淆的字符oOLl和数字01，)
     * @return string
     */
    public static function random(int $len = 4, int $type = 0): string
    {
        $str = '';
        switch ($type) {
            case self::RANDOM_ALPHA://大小写中英文
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case self::RANDOM_NUMBER://数字
                $chars = str_repeat('0123456789', 3);
                break;
            case self::RANDOM_UPPERCASE_ALPHA://大写字母
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case self::RANDOM_LOWERCASE_ALPHA://小写字母
                $chars = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case 4:
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                break;
            default:
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        }
        if ($len > 10) { // 位数过长重复字符串一定次数
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = substr($chars, 0, $len);
        } else {
            // 中文随机字
            for ($i = 0; $i < $len; $i++) {
                $str .= self::msubstr($chars, (int)floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
            }
        }
        return $str;
    }

    /**
     * 获取首字母,包括汉字
     * @param string $works
     * @return string
     */
    public static function getFirstChar(string $works): string
    {
        $firstchar_ord = ord(strtoupper($works{0}));# 获取ascii码
        if (($firstchar_ord >= 65 and $firstchar_ord <= 91) or ($firstchar_ord >= 48 and $firstchar_ord <= 57)) return $works{0};
        $s = iconv('UTF-8', 'gb2312', $works);
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 and $asc <= -20284) return 'A';
        if ($asc >= -20283 and $asc <= -19776) return 'B';
        if ($asc >= -19775 and $asc <= -19219) return 'C';
        if ($asc >= -19218 and $asc <= -18711) return 'D';
        if ($asc >= -18710 and $asc <= -18527) return 'E';
        if ($asc >= -18526 and $asc <= -18240) return 'F';
        if ($asc >= -18239 and $asc <= -17923) return 'G';
        if ($asc >= -17922 and $asc <= -17418) return 'H';
        if ($asc >= -17417 and $asc <= -16475) return 'J';
        if ($asc >= -16474 and $asc <= -16213) return 'K';
        if ($asc >= -16212 and $asc <= -15641) return 'L';
        if ($asc >= -15640 and $asc <= -15166) return 'M';
        if ($asc >= -15165 and $asc <= -14923) return 'N';
        if ($asc >= -14922 and $asc <= -14915) return 'O';
        if ($asc >= -14914 and $asc <= -14631) return 'P';
        if ($asc >= -14630 and $asc <= -14150) return 'Q';
        if ($asc >= -14149 and $asc <= -14091) return 'R';
        if ($asc >= -14090 and $asc <= -13319) return 'S';
        if ($asc >= -13318 and $asc <= -12839) return 'T';
        if ($asc >= -12838 and $asc <= -12557) return 'W';
        if ($asc >= -12556 and $asc <= -11848) return 'X';
        if ($asc >= -11847 and $asc <= -11056) return 'Y';
        if ($asc >= -11055 and $asc <= -10247) return 'Z';
        return '';
    }


//    /**
//     * 生成指定长度的62进制随机字符串
//     * @param int $len
//     * @return string
//     */
//    public static function random62($len = 6)
//    {
//        $code = self::CAHRS;
//        $rand = $code[rand(0, 62)]
//            . strtoupper(dechex(date('m')))
//            . date('d') . substr(time(), -5)
//            . substr(microtime(), 2, 5)
//            . sprintf('%02d', rand(0, 99));
//        for (
//            $a = md5($rand),
//            $d = '',
//            $f = 0;
//
//            $f < $len;
//
//            $g = ord($a[$f]),
//            $d .= $code[($g ^ ord($a[$f + 8])) - $g & 0x3D],
//            $f++
//        ) ;
//        return $d;
//    }


    /**
     * 判断是否以目标字符串结尾
     * @param string $string
     * @param string $tail
     * @return bool
     */
    public static function isEndWith(string $string, string $tail): bool
    {
        return substr($string, -strlen($tail)) === $tail;
    }


    /**
     * 删除尾巴
     * @param string $string
     * @param string $tail
     * @return string
     */
    public static function striptail(string $string, string $tail): string
    {
        if (substr($string, -strlen($tail)) === $tail) {
            $len = strlen($string) - strlen($tail);
            return $len ? substr($string, 0, $len) : '';
        } else {
            return $string;
        }
    }


    /**
     * 将C风格字符串转换成JAVA风格字符串
     * C风格      如： sub_string
     * JAVA风格   如： SubString
     * @param string $str
     * @param bool $c2java 转换方向，默认C 风格转 Java 风格
     * @return string 返回风格转换后的类型
     */
    public static function convertIdentifyStyle(string $str, bool $c2java = true): string
    {
        return $c2java ? ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $str)) :
            strtolower(ltrim(preg_replace('/[A-Z]/', '_\\0', $str), '_'));
    }

}