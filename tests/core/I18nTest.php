<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/16 0016
 * Time: 22:43
 */
declare(strict_types=1);

namespace driphp\tests\core;


use driphp\core\I18n;
use driphp\Kernel;
use driphp\tests\UnitTest;

class I18nTest extends UnitTest
{
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        defined('REDIRECT_KEY') or define('REDIRECT_KEY', 'The page will redirect after %d seconds');
    }
    /**
     * @return void
     */
    public function testBasic()
    {
        $string = REDIRECT_KEY;
        $this->assertTrue($string === I18n::getInstance('en')->get($string));
        $this->assertTrue($string === I18n::getInstance('en_US')->get($string));
        $this->assertTrue($string === I18n::getInstance('en_GB')->get($string));
        $this->assertTrue('页面将在 %d 秒钟后跳转' === I18n::getInstance('zh_CN')->get($string));
        $this->assertTrue('頁面將在 %d 秒鐘後跳轉' === I18n::getInstance('zh_TW')->get($string));
    }

//    /**
//     * @throws \sharin\KernelException
//     * @throws \driphp\throws\io\FileWriteException
//     */
//    public function testProjectDefine()
//    {
//        Kernel::configuration(DRI_PATH_PROJECT . 'i18n/zh_CN.php', [
//            REDIRECT_KEY => 'XXX %d YYY',
//            'hello' => '你好啊',
//        ]);
//        $I18n = I18n::getInstance('zh_CN');
//        $I18n->load(true);
//
//        $this->assertTrue('XXX %d YYY' === $I18n->get(REDIRECT_KEY));
//        $this->assertTrue('你好啊' === $I18n->get('hello'));
//    }


}