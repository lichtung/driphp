<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/8
 * Time: 15:55
 */

namespace driphp\library\encrypt;

use Exception;
use driphp\Component;
use driphp\library\encrypt\openssl\KeyResourceException;

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
 * @method OpenSSL factory(array $config = []) static
 * @package driphp\library\encrypt
 */
class OpenSSL extends Component
{
    /**
     * @var resource
     */
    private $privateKeyResource = null;
    /**
     * @var resource
     */
    private $publicKeyResource = null;

    protected $config = [
        'private_key' => '',
        'public_key' => '',
        'encrypt_block_size' => 200, # Block size for encryption block cipher this for 2048 bit key for example, leaving some room
        'decrypt_block_size' => 256, # Block size for decryption block cipher this again for 2048 bit key
    ];

    /**
     * @throws KeyResourceException
     */
    protected function initialize()
    {
        $public_key = $this->config['public_key'];
        $private_key = $this->config['private_key'];
        if (is_file($public_key)) $public_key = file_get_contents($public_key);
        $this->publicKeyResource = openssl_pkey_get_public($public_key);
        if (false === $this->publicKeyResource) throw new KeyResourceException('invalid public key');

        if (is_file($private_key)) $private_key = file_get_contents($private_key);
        $this->privateKeyResource = openssl_pkey_get_private($private_key);
        if (false === $this->privateKeyResource) throw new KeyResourceException('invalid private key');
    }


    /**
     * 生成公私钥
     * @param string $outputDir 输出目录
     * @return string
     */
    public static function generate(string $outputDir = ''): string
    {
        $outputDir or $outputDir = __DIR__ . '/../../runtime/';
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);
        $rsa_private_key = $outputDir . '/rsa_private_key.pem';
        $private_key = $outputDir . '/private_key.pem';
        $rsa_public_key = $outputDir . '/rsa_public_key.pem';
        $result = [];
        # rsa私钥生成
        exec("openssl genrsa -out $rsa_private_key 2048", $result);
        # rsa公钥生成
        exec("openssl pkcs8 -topk8 -inform PEM -in $rsa_private_key -outform PEM -nocrypt -out $private_key", $result);
        # 私钥格式转换成pkcs8格式
        exec("openssl rsa -in $rsa_private_key -pubout -out $rsa_public_key", $result);
        return implode("\n", $result);
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
        $encrypted = '';
        $plainData = str_split($plainData, $this->config['encrypt_block_size']);
        foreach ($plainData as $chunk) {
            $partialEncrypted = '';
            //using for example OPENSSL_PKCS1_PADDING as padding
            if ($isPrivate) {
                $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $this->privateKeyResource, OPENSSL_PKCS1_PADDING);
            } else {
                $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $this->publicKeyResource, OPENSSL_PKCS1_PADDING);
            }
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
        $decrypted = '';
        //decode must be done before split for getting the binary String
        $data = str_split($decode ? base64_decode($data) : $data, $this->config['decrypt_block_size']);
        foreach ($data as $chunk) {
            $partial = '';
            //be sure to match padding
            if ($isPublic) {
                $decryptionOK = openssl_public_decrypt($chunk, $partial, $this->publicKeyResource, OPENSSL_PKCS1_PADDING);
            } else {
                $decryptionOK = openssl_private_decrypt($chunk, $partial, $this->privateKeyResource, OPENSSL_PKCS1_PADDING);
            }
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