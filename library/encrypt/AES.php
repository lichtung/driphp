<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 15:09
 */

namespace driphp\library\encrypt;

use driphp\library\encrypt\aes\Encipher;
use driphp\library\encrypt\aes\Decipher;
use driphp\Component;

/**
 * Class AES 高级加密标准(Advanced Encryption Standard)
 *  用于替代前任标准DES,在软件和硬件上都能实现快速的加解密
 * @method AES factory(array $config = []) static
 * @package driphp\library\encrypt
 */
class AES extends Component
{

    protected $config = [
        'key' => 'a1236547890',
        'method' => self::METHOD_AES_256_CFB,
    ];

    protected function initialize()
    {
        $this->_key = $this->config['key'];
        $this->_method = strtolower($this->config['method']);
        $iv_size = openssl_cipher_iv_length($this->_method);
        $iv = openssl_random_pseudo_bytes($iv_size);
        $this->_cipher = $this->getEncipher($this->_key, $this->_method, $iv);
    }

    /**
     * @var string 密钥
     */
    private $_key;
    /**
     * @var string 加密方法
     */
    private $_method;
    /**
     * @var Encipher
     */
    private $_cipher;
    /**
     * @var Decipher
     */
    private $_decipher;

    // 预定义方法
    const METHOD_AES_128_CFB = 'aes-128-cfb';
    const METHOD_AES_192_CFB = 'aes-192-cfb';
    const METHOD_AES_256_CFB = 'aes-256-cfb';
    const METHOD_CAMELLIA_256_CFB = 'camellia-256-cfb';

    private $_bytesToKeyResults = [];
    private static $_encryptTable = [];
    private static $_decryptTable = [];
    private $_cipherIv;
    private $_ivSent;

    # 支持的方法合集
    const AVAILABLE_METHODS = [
        self::METHOD_AES_128_CFB => [16, 16],
        self::METHOD_AES_192_CFB => [24, 16],
        self::METHOD_AES_256_CFB => [32, 16],
        'bf-cfb' => [16, 8],
        'camellia-128-cfb' => [16, 16],
        'camellia-192-cfb' => [24, 16],
        self::METHOD_CAMELLIA_256_CFB => [32, 16],
        'cast5-cfb' => [16, 8],
        'des-cfb' => [8, 8],
        'idea-cfb' => [16, 8],
        'rc2-cfb' => [16, 8],
        'seed-cfb' => [16, 16]
    ];

    /**
     * 加密
     * @param string $buffer 待加密的字符串
     * @param bool $encode 是否编码后返回（base64）
     * @return string
     */
    public function encrypt(string $buffer, bool $encode = false): string
    {

        if ($this->_method) {
            $result = $this->_cipher->update($buffer);
            if (!$this->_ivSent) {
                $this->_ivSent = true;
                $result = $this->_cipherIv . $result;
            }
        } else {
            $result = self::substitute(self::$_encryptTable, $buffer);
        }
        return $encode ? urlencode(base64_encode($result)) : $result;
    }

    /**
     * 解密
     * @param string $buffer 待解密的字符串
     * @param bool $decode 是否先进行base64解码
     * @return string
     */
    public function decrypt(string $buffer, bool $decode = false): string
    {
        $decode and $buffer = base64_decode(urldecode($buffer));
        if ($this->_method) {
            if (!$this->_decipher) {
                $decipher_iv_len = self::AVAILABLE_METHODS[$this->_method][1];
                $decipher_iv = substr($buffer, 0, $decipher_iv_len);
                $this->_decipher = $this->getDecipher($this->_key, $this->_method, $decipher_iv);
                $result = $this->_decipher->update(substr($buffer, $decipher_iv_len));
            } else {
                $result = $this->_decipher->update($buffer);
            }
        } else {
            $result = self::substitute(self::$_decryptTable, $buffer);
        }
        return $result;
    }

    /**
     * @param array $table
     * @param string $buf
     * @return string
     */
    private static function substitute(array $table, $buf)
    {
        $i = 0;
        $len = strlen($buf);
        while ($i < $len) {
            $buf[$i] = chr($table[ord($buf[$i])]);
            $i++;
        }
        return (string)$buf;
    }

    private function getEncipher($password, $method, $iv)
    {
        $method = strtolower($method);
        $m = self::AVAILABLE_METHODS[$this->_method];
        $ref = $this->evpBytesToKey($password, $m[0], $m[1]);
        $key = $ref[0];
        $iv_ = $ref[1];
        if ($iv == null) {
            $iv = $iv_;
        }
        $this->_cipherIv = substr($iv, 0, $m[1]);
        $iv = substr($iv, 0, $m[1]);
        if ($method === 'rc4-md5') {
            $method = 'rc4';
            $key = md5($key . $iv);
            $iv = '';
        }
        return new Encipher($method, $key, $iv);
    }


    private function getDecipher($password, $method, $iv)
    {
        $method = strtolower($method);
        $m = self::AVAILABLE_METHODS[$this->_method];
        $ref = $this->evpBytesToKey($password, $m[0], $m[1]);
        $key = $ref[0];
        $iv_ = $ref[1];
        if ($iv == null) {
            $iv = $iv_;
        }
        $iv = substr($iv, 0, $m[1]);
        if ($method === 'rc4-md5') {
            $method = '';
            $key = md5($key . $iv);
            $iv = '';
        }
        return new Decipher($method, $key, $iv);
    }

    /**
     * 实现openssl evp_bytetokey,从输入密码产生了密钥key和初始化向量iv
     * @param string $password
     * @param int $key_len
     * @param int $iv_len
     * @return array
     */
    private function evpBytesToKey($password, $key_len, $iv_len)
    {
        $cache_key = "$password:$key_len:$iv_len";
        if (isset($this->_bytesToKeyResults[$cache_key])) {
            return $this->_bytesToKeyResults[$cache_key];
        }
        $m = [];
        $i = 0;
        $count = 0;
        while ($count < $key_len + $iv_len) {
            $data = $password;
            if ($i > 0) {
                $data = $m[$i - 1] . $password;
            }
            $d = md5($data, true);
            $m[] = $d;
            $count += strlen($d);
            $i += 1;
        }
        $ms = '';
        foreach ($m as $buf) {
            $ms .= $buf;
        }

        $key = substr($ms, 0, $key_len);
        $iv = substr($ms, $key_len, $key_len + $iv_len);
        return $this->_bytesToKeyResults[$password] = [$key, $iv];
    }

    private static function merge_sort($array, $comparison)
    {
        if (count($array) < 2) {
            return $array;
        }
        $middle = ceil(count($array) / 2);
        return self::merge(self::merge_sort(self::slice($array, 0, $middle), $comparison),
            self::merge_sort(self::slice($array, $middle), $comparison), $comparison);
    }

    private static function slice($table, $start, $end = null)
    {
        $table = array_values($table);
        if ($end) {
            return array_slice($table, $start, $end);
        } else {
            return array_slice($table, $start);
        }
    }


    private static function merge($left, $right, $comparison)
    {
        $result = [];
        while ((count($left) > 0) && (count($right) > 0)) {
            if (call_user_func($comparison, $left[0], $right[0]) <= 0) {
                $result[] = array_shift($left);
            } else {
                $result[] = array_shift($right);
            }
        }
        while (count($left) > 0) {
            $result[] = array_shift($left);
        }
        while (count($right) > 0) {
            $result[] = array_shift($right);
        }
        return $result;
    }
}