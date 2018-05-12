<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 14:57
 */
declare(strict_types=1);


namespace sharin\library\encrypt;


class Decipher extends Encipher
{
    public function update($data)
    {
        if (strlen($data) == 0)
            return '';
        $tl = strlen($this->_tail);
        if ($tl)
            $data = $this->_tail . $data;
        $b = openssl_decrypt($data, $this->_algorithm, $this->_key, OPENSSL_RAW_DATA, $this->_iv);
        $result = substr($b, $tl);
        $dataLength = strlen($data);
        $mod = $dataLength % $this->_ivLength;
        if ($dataLength >= $this->_ivLength) {
            $iPos = -($mod + $this->_ivLength);
            $this->_iv = substr($data, $iPos, $this->_ivLength);
        }
        $this->_tail = $mod != 0 ? substr($data, -$mod) : '';
        return (string)$result;
    }
}