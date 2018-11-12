<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 10:43
 */

namespace controller;


class Captcha
{
    /**
     * @throws \driphp\library\captcha\CaptchaException
     */
    public function flush()
    {
        $captcha = \driphp\library\Captcha::factory();
        echo $captcha->header()->generate(function ($code) {
            header("code: $code");
            header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header("content-type: image/png");
        });
    }
}