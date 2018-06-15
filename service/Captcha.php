<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 19:52
 */
declare(strict_types=1);


namespace driphp\service;


use driphp\core\Service;
use Gregwar\Captcha\CaptchaBuilder;

/**
 * Class CaptchaService
 * TODO:
 *
 * @see https://github.com/Gregwar/Captcha
 *
 * @method Captcha getInstance(string $index = '') static
 *
 * @package driphp\service
 */
class Captcha extends Service
{

    /**
     * @var CaptchaBuilder
     */
    private $builder = null;

    /**
     * @param string $phrase
     * @param string $path The path to save the file to.
     * @param int $quality quality is optional, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file).
     *        The default is the default IJG quality value (about 75).
     * @return void
     */
    public function save(string $phrase, string $path, int $quality = 75): void
    {
        $this->builder = new CaptchaBuilder($phrase);
        $this->builder->build()->save($path, $quality);
    }

    /**
     * create a captcha and output it directly
     * @param string $phrase
     * @return void
     */
    public function output(string $phrase): void
    {
        $this->builder = new CaptchaBuilder($phrase);
        $this->builder->build()->output();
    }

    public function inline(string $phrase): void
    {
        $this->builder = new CaptchaBuilder($phrase);
        $this->builder->build()->inline();
    }

    public function getPhrase(): string
    {
        return $this->builder->getPhrase();
    }
}