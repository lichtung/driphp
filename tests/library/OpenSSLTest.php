<?php
/**
 * User: linzhv@qq.com
 * Date: 15/01/2018
 * Time: 16:38
 */
declare(strict_types=1);

namespace driphp\test\library;

use driphp\core\Chars;
use driphp\library\encrypt\OpenSSL;
use driphp\tests\UnitTest;

class OpenSSLTest extends UnitTest
{

    private $rsa_private_key;
    private $rsa_public_key;

    public function __construct()
    {
        parent::__construct();
        $this->rsa_private_key = DRI_PATH_FRAMEWORK . 'runtime/rsa_private_key.pem';
        $this->rsa_public_key = DRI_PATH_FRAMEWORK . 'runtime/rsa_public_key.pem';
    }

    public function testCreate()
    {
        is_file($this->rsa_private_key) and unlink($this->rsa_private_key);
        is_file($this->rsa_public_key) and unlink($this->rsa_public_key);
        OpenSSL::generate();
        $this->assertTrue(true === is_file($this->rsa_public_key));
        $this->assertTrue(true === is_file($this->rsa_private_key));
    }


    /**
     * @return OpenSSL
     * @throws \Exception
     */
    public function testEncryptInPrivateAndDecryptInPublic()
    {
        $openssl = OpenSSL::factory([
            'private_key' => $this->rsa_private_key,
            'public_key' => $this->rsa_public_key,
        ]);
        $i = 0;
        while ($i++ <= 4097) { # 1-4096
            $str = Chars::random($i);
            $encryptStr = $openssl->encryptInPrivate($str, true);
            $decryptStr = $openssl->decryptInPublic($encryptStr, true);
            $this->assertTrue($str !== $encryptStr);
            $this->assertTrue($encryptStr !== $decryptStr);
            $this->assertTrue($str === $decryptStr);
            echo "[$i]";
        }
        return $openssl;
    }

    /**
     * @depends testEncryptInPrivateAndDecryptInPublic
     * @param OpenSSL $openssl
     * @throws \Exception
     */
    public function testEncryptInPublicAndDecryptInPrivate(OpenSSL $openssl)
    {
        $i = 0;
        while ($i++ <= 4097) { # 1-4096
            $str = Chars::random($i);
            $encryptStr = $openssl->encryptInPublic($str, true);
            $decryptStr = $openssl->decryptInPrivate($encryptStr, true);
            $this->assertTrue($str !== $encryptStr);
            $this->assertTrue($encryptStr !== $decryptStr);
            $this->assertTrue($str === $decryptStr);
            echo "[$i]";
        }
    }
}