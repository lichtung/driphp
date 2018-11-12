<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 15:39
 */

namespace driphp\library\encrypt;


use driphp\Component;

/**
 * Class Base64X Base64含密钥加密
 * @method Base64X factory(array $config = []) static
 * @package driphp\library\encrypt
 */
class Base64X extends Component
{
    protected $config = [
        'key' => 'micro!apache:@$#,214',
        'expiry' => 3600, # 密文有效期,单位s,0 为永久有效
    ];
    /** @var string */
    private $key;
    /** @var int */
    private $expiry;

    protected function initialize()
    {
        $this->key = sha1(md5($this->config['key']));
        $this->expiry = intval($this->config['expiry']);
    }


    /**
     * 字符加解密，一次一密,可定时解密有效
     *
     * @param string $string 原文或者密文
     * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
     */
    public function encode($string): string
    {
        $ckey_length = 4;
        $key = $this->key; //解密密匙
        $keya = md5(substr($key, 0, 16));         //做数据完整性验证
        $keyb = md5(substr($key, 16, 16));         //用于变化生成的密文 (初始化向量IV)
        $keyc = substr(md5(microtime()), -$ckey_length);
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = sprintf('%010d', $this->expiry ? $this->expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        $box = range(0, 255);
        // 打乱密匙簿，增加随机性
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 加解密，从密匙簿得出密匙进行异或，再转成字符
        $result = '';
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        $result = $keyc . str_replace('=', '', base64_encode($result));
        $result = str_replace(array('+', '/', '='), array('-', '_', '.'), $result);
        return $result;
    }

    /**
     * 字符加解密，一次一密,可定时解密有效
     *
     * @param string $string 原文或者密文
     * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
     */
    public function decode($string): string
    {
        $string = str_replace(['-', '_', '.'], ['+', '/', '='], $string);
        $ckey_length = 4;
        $key = $this->key; //解密密匙
        $keya = md5(substr($key, 0, 16));         //做数据完整性验证
        $keyb = md5(substr($key, 16, 16));         //用于变化生成的密文 (初始化向量IV)
        $keyc = substr($string, 0, $ckey_length);

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = base64_decode(substr($string, $ckey_length));
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 打乱密匙簿，增加随机性
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 加解密，从密匙簿得出密匙进行异或，再转成字符
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
            && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
        ) {
            return substr($result, 26);
        } else {
            return '';
        }
    }
}