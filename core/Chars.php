<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 19:29
 */

namespace driphp\core;

/**
 * Class Chars
 * @package driphp\core
 */
class Chars
{

    const CHARS62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * 十进制数转换成62进制
     *
     * @param integer $num
     * @return string
     */
    public static function decto62($num)
    {
        $to = 62;
        $dict = self::CHARS62;
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
     * 62进制数转换成十进制数
     *
     * @param string $num
     * @return string
     */
    public static function decfrom62($num)
    {
        $from = 62;
        $num = strval($num);
        $dict = self::CHARS62;
        $len = strlen($num);
        $dec = 0;
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($dict, $num[$i]);
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
        }
        return $dec;
    }

    /**
     * 产生随机字符串
     * @param int $length
     * @param string $characters
     * @return string
     */
    public static function random(int $length = 32, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $string = '';
        $len = strlen($characters);
        for ($i = $length; $i > 0; $i--) {
            $string .= $characters[mt_rand(0, $len - 1)] ?? '';
        }
        return $string;
    }

    /**
     * 获取首字母,包括汉字
     * @param string $works
     * @return string
     */
    public static function firstChar(string $works): string
    {
        $first_char_ord = ord(strtoupper($works{0}));# 获取ascii码
        if (($first_char_ord >= 65 and $first_char_ord <= 91) or ($first_char_ord >= 48 and $first_char_ord <= 57)) return $works{0};
        $s = iconv('UTF-8', 'gb2312', $works); # 转 GB2312 编码
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

    /**
     * 去除代码中的空白和注释
     * @param string $content 代码内容
     * @return string
     */
    public static function stripWhiteSpace($content)
    {
        $stripStr = '';
        //分析php源码
        $tokens = token_get_all($content);
        $last_space = false;
        for ($i = 0, $len = count($tokens); $i < $len; $i++) {
            if (is_string($tokens[$i])) {
                $last_space = false;
                $stripStr .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    //过滤各种php注释
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    //过滤空格
                    case T_WHITESPACE:
                        if (!$last_space) {
                            $stripStr .= ' ';
                            $last_space = true;
                        }
                        break;
                    case T_START_HEREDOC:
                        $stripStr .= "<<<lite\n";
                        break;
                    case T_END_HEREDOC:
                        $stripStr .= "lite;\n";
                        for ($k = $i + 1; $k < $len; $k++) {
                            if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                                $i = $k;
                                break;
                            } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                        break;
                    default:
                        $last_space = false;
                        $stripStr .= $tokens[$i][1];
                }
            }
        }
        return $stripStr;
    }
}