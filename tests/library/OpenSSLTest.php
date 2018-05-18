<?php
/**
 * User: linzhv@qq.com
 * Date: 15/01/2018
 * Time: 16:38
 */
declare(strict_types=1);

namespace sharin\test\library;

use sharin\library\OpenSSL;
use sharin\tests\UniTest;

class OpenSSLTest extends UniTest
{
    private $rsa_private_key;
    private $rsa_public_key;

    public function __construct()
    {
        parent::__construct();
        $this->rsa_private_key = SR_PATH_FRAMEWORK . 'runtime/rsa_private_key.pem';
        $this->rsa_public_key = SR_PATH_FRAMEWORK . 'runtime/rsa_public_key.pem';
    }

    public function testcreate()
    {
        $rsa_private_key = $this->rsa_private_key;
        $private_key = SR_PATH_FRAMEWORK . 'runtime/private_key.pem';
        $rsa_public_key = $this->rsa_public_key;
        is_file($rsa_private_key) and unlink($rsa_private_key);
        is_file($rsa_public_key) and unlink($rsa_public_key);
        is_file($private_key) and unlink($private_key);
        OpenSSL::generate();
        $this->assertTrue(true === is_file($rsa_public_key));
        $this->assertTrue(true === is_file($rsa_private_key));
        $this->assertTrue(true === is_file($private_key));
    }

    /**
     * @return OpenSSL
     * @throws \sharin\SharinException
     */
    public function testEncryptInPrivateAndDecryptInPublic()
    {
        $openssl = new OpenSSL($this->rsa_public_key, $this->rsa_private_key);
        $str = '123';
        $newstr = $openssl->encryptInPrivate($str, true);
        $decstr = $openssl->decryptInPublic($newstr, true);
        $this->assertTrue($str === $decstr);
        return $openssl;
    }

    /**
     * @depends testEncryptInPrivateAndDecryptInPublic
     * @param OpenSSL $openssl
     * @return void
     * @throws \sharin\SharinException
     */
    public function testEncryptInPublicAndDecryptInPrivate(OpenSSL $openssl)
    {
        $str = '123';
        $newstr = $openssl->encryptInPublic($str, true);
        $decstr = $openssl->decryptInPrivate($newstr, true);
        $this->assertTrue($str === $decstr);
    }
}