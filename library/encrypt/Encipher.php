<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 14:58
 */
declare(strict_types=1);


namespace sharin\library\encrypt;

/**
 * Class Encipher 密文书写器
 * @package sharin\library\encrypt
 */
class Encipher
{
    protected $_algorithm;
    protected $_key;
    protected $_iv;
    protected $_tail = '';
    protected $_ivLength;

    /**
     * Encipher constructor.
     * @param string $method 加密方法
     * @param string $key 加密密钥
     * @param $iv
     */
    public function __construct($method, $key, $iv)
    {
        $this->_algorithm = $method;
        $this->_key = $key;
        $this->_iv = $iv;
        $this->_ivLength = openssl_cipher_iv_length($method);
    }

    public function update($data)
    {
        if (strlen($data) == 0)
            return '';
        $tl = strlen($this->_tail);
        if ($tl)
            $data = $this->_tail . $data;
        $b = openssl_encrypt($data, $this->_algorithm, $this->_key, OPENSSL_RAW_DATA, $this->_iv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength % $this->_ivLength;
        if ($dataLength >= $this->_ivLength) {
            $iPos = -($mod + $this->_ivLength);
            $this->_iv = substr($b, $iPos, $this->_ivLength);
        }
        $this->_tail = $mod != 0 ? substr($data, -$mod) : '';
        return (string)$result;
    }


}