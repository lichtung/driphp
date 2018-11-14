<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 15:20
 */

namespace driphp\test\library;


use driphp\core\Chars;
use driphp\library\encrypt\AES;
use driphp\tests\UnitTest;

class AESTest extends UnitTest
{
    /**
     * 两种加密方法同时加密解密
     */
    public function testRun()
    {
        $aes = AES::factory([
            'key' => 'this_is_aes_key',
            'method' => AES::METHOD_AES_256_CFB,
        ]);
        $camellia = AES::factory([
            'key' => 'this_is_camellia_key',
            'method' => AES::METHOD_CAMELLIA_256_CFB,
        ]);
        $i = 0;
        while ($i++ <= 4097) { # 1-4096
            $str = Chars::random($i);
            $enAes = $aes->encrypt($str);
            $enCam = $camellia->encrypt($str);

            $deAes = $aes->decrypt($enAes);
            $deCam = $camellia->decrypt($enCam);

            $this->assertTrue($str !== $enAes);
            $this->assertTrue($str !== $enCam);
            $this->assertTrue($enAes !== $enCam);

            $this->assertTrue($enAes !== $deAes);
            $this->assertTrue($enCam !== $deCam);

            $this->assertTrue($str === $deAes);
            $this->assertTrue($str === $deCam);
        }
        $this->assertTrue(true);
    }
}