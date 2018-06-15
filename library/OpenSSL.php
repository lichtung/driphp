<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 15:05
 */
declare(strict_types=1);


namespace driphp\library;

use driphp\throws\library\OpenSSLException as Exception;

/**
 * Class OpenSSL
 *
 *
 * Note:
 * "- openssl_private_encrypt can encrypt a maximum of 117 chars at one time."
 *  openssl_private_encrypt函数一次加密的长度有限制，它依赖于key的长度
 *
 * This depends on the length of $key:
 *
 * - For a 1024 bit key length => max number of chars (bytes) to encrypt = 1024/8 - 11(when padding used) = 117 chars (bytes).
 * - For a 2048 bit key length => max number of chars (bytes) to encrypt = 2048/8 - 11(when padding used) = 245 chars (bytes).
 * ... and so on
 *
 * By the way, if openssl_private_encrypt fails because of data size you won't get anything but just false as returned value,
 * the same for openssl_public_decrypt() on decryption.
 * 顺便一提，如有openssl_private_encrypt因为数据长度过大而调用失败，你除了返回的false外什么也得不到，openssl_public_decrypt解密时也一样
 *
 *
 * "- the encrypted output string is always 129 char length. If you use base64_encode on the encrypted output, it will give always 172 chars,
 * with the last always "=" (filler)"
 * 加密输出的字符串长度总是129，如果你使用base64编码返回结果，它的长度就会变成172（base64编码使然）
 *
 * This again depends on the length of $key:
 *
 * - For a 1024 bit key length => encrypted number of raw bytes is always a block of 128 bytes (1024 bits) by RSA design.
 * - For a 2048 bit key length => encrypted number of raw bytes is always a block of 256 bytes (2048 bits) by RSA design.
 * ... and so on
 *
 * About base64_encode output length, it depends on what you encode (meaning it depends on the bytes resulting after encryption),
 * but in general the resulting encoded string will be about a 33% bigger (for 128 bytes bout 170 bytes and for 256 bytes about
 * 340 bytes).
 *
 * @package driphp\library
 */
class OpenSSL
{
    /**
     *
     * Block size for encryption block cipher
     * this for 2048 bit key for example, leaving some room
     */
    const ENCRYPT_BLOCK_SIZE = 200;
    /**
     * Block size for decryption block cipher
     * this again for 2048 bit key
     */
    const DECRYPT_BLOCK_SIZE = 256;

    private $privateKey = '';

    private $publicKey = '';
    /**
     * @var resource
     */
    private $privateKeyResource = null;
    /**
     * @var resource
     */
    private $publicKeyResource = null;

    /**
     * Rsa constructor.
     * @param string $public_key 公钥内容或者存储位置
     * @param string $private_key 私钥内容或者存储位置
     */
    public function __construct(string $public_key, string $private_key)
    {
        $this->privateKey = $private_key;
        $this->publicKey = $public_key;
    }

    /**
     * @return resource
     * @throws Exception
     */
    public function getPublicKeyResource()
    {
        if (!$this->publicKeyResource) {
            if (is_file($this->publicKey)) $this->publicKey = file_get_contents($this->publicKey);
            $this->publicKeyResource = openssl_pkey_get_public($this->publicKey);
            if (false === $this->publicKeyResource) throw new Exception('invalid public key');
        }
        return $this->publicKeyResource;
    }

    /**
     * @return bool|resource
     * @throws Exception
     */
    public function getPrivateKeyResource()
    {
        if (!$this->privateKeyResource) {
            if (is_file($this->privateKey)) $this->privateKey = file_get_contents($this->privateKey);
            $this->privateKeyResource = openssl_pkey_get_private($this->privateKey);
            if (false === $this->privateKeyResource) throw new Exception('invalid private key');
        }
        return $this->privateKeyResource;
    }

    /**
     * 私钥加密
     * @param string $plainData 加密数据
     * @param bool $encode 加密后的数据包含特殊字符串，是否对之进行编码
     * @return string
     * @throws Exception
     */
    public function encryptInPrivate(string $plainData, bool $encode = false): string
    {
        return $this->encrypt($plainData, true, $encode);
    }

    /**
     * 加密数据
     * @param string $plainData 加密数据
     * @param bool $isPrivate 是否用私钥加密
     * @param bool $encode 加密后的数据包含特殊字符串,是否对之进行编码
     * @return string
     * @throws Exception
     */
    public function encrypt(string $plainData, bool $isPrivate = true, bool $encode = false): string
    {
        if ($isPrivate) {
            $pemKey = $this->getPrivateKeyResource();
            $func = 'openssl_private_encrypt';
        } else {
            $pemKey = $this->getPublicKeyResource();
            $func = 'openssl_public_encrypt';
        }
        $encrypted = '';
        $plainData = str_split($plainData, self::ENCRYPT_BLOCK_SIZE);
        foreach ($plainData as $chunk) {
            $partialEncrypted = '';
            //using for example OPENSSL_PKCS1_PADDING as padding
            $encryptionOk = call_user_func_array($func, [$chunk, $partialEncrypted, $pemKey, OPENSSL_PKCS1_PADDING]);
            if ($encryptionOk === false) {
                //also you can return and error. If too big this will be false
                return '';
            }
            $encrypted .= $partialEncrypted;
        }
        return $encode ? base64_encode($encrypted) : $encrypted;
    }

    /**
     * @param string $data
     * @param bool $isPublic
     * @param bool $decode
     * @return string
     * @throws Exception
     */
    public function decrypt(string $data, bool $isPublic = true, bool $decode = false): string
    {
        if ($isPublic) {
            $pemKey = $this->getPublicKeyResource();
            $func = 'openssl_public_decrypt';
        } else {
            $pemKey = $this->getPrivateKeyResource();
            $func = 'openssl_private_decrypt';
        }
        $decrypted = '';
        //decode must be done before split for getting the binary String
        $data = str_split($decode ? base64_decode($data) : $data, self::DECRYPT_BLOCK_SIZE);
        foreach ($data as $chunk) {
            $partial = '';
            //be sure to match padding
            $decryptionOK = $func($chunk, $partial, $pemKey, OPENSSL_PKCS1_PADDING);
            if ($decryptionOK === false) {
                //here also processed errors in decryption. If too big this will be false
                return '';
            }
            $decrypted .= $partial;
        }
        return $decrypted;
    }

    /**
     * 公钥解密私钥加密数据
     * @param string $data 加密后的数据
     * @param boolean $decode 是否对数据进行解码
     * @return string
     * @throws Exception
     */
    public function decryptInPublic(string $data, bool $decode = false): string
    {
        return $this->decrypt($data, true, $decode);
    }

    /**
     * 公钥加密
     * @param string $data 加密数据
     * @param bool $encode 加密后的数据包含特殊字符串
     * @return string
     * @throws Exception
     */
    public function encryptInPublic(string $data, bool $encode = false): string
    {
        return $this->encrypt($data, false, $encode);
    }

    /**
     * 私钥解密公钥加密数据
     * @param string $data 加密后的数据
     * @param boolean $decode 是否对数据进行解码
     * @return string
     * @throws Exception
     */
    public function decryptInPrivate(string $data, bool $decode = false): string
    {
        return $this->decrypt($data, false, $decode);
    }


}