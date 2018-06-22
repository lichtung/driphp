<?php
/**
 * User: linzhv@qq.com
 * Date: 15/01/2018
 * Time: 16:38
 */
declare(strict_types=1);

namespace driphp\test\library;

use driphp\library\RSA;
use driphp\tests\UniTest;

class OpenSSLTest extends UniTest
{
    private $rsa_private_key;
    private $rsa_public_key;

    public function __construct()
    {
        parent::__construct();
        $this->rsa_private_key = __DIR__ . '/rsa_private.key';
        $this->rsa_public_key = __DIR__ . '/rsa_public.key';
    }

    /**
     * @return RSA
     * @throws \driphp\throws\library\OpenSSLException
     */
    public function testEncryptInPrivateAndDecryptInPublic()
    {
        $openssl = RSA::getInstance([
            'private_key' => $this->rsa_private_key, # 公钥内容或者存储位置
            'public_key' => $this->rsa_public_key,
        ]);
        $str = '123';
        $newStr = $openssl->encryptInPrivate($str, true);
        $decStr = $openssl->decryptInPublic($newStr, true);
        $this->assertTrue($str === $decStr);
        return $openssl;
    }

    /**
     * @depends testEncryptInPrivateAndDecryptInPublic
     * @param RSA $openssl
     * @return void
     * @throws \driphp\throws\library\OpenSSLException
     */
    public function testEncryptInPublicAndDecryptInPrivate(RSA $openssl)
    {
        $str = '123';
        $newStr = $openssl->encryptInPublic($str, true);
        $decStr = $openssl->decryptInPrivate($newStr, true);
        $this->assertTrue($str === $decStr);
    }
}