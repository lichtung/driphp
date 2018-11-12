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
     * @param string $id
     * @throws \driphp\library\captcha\CaptchaException
     */
    public function flush($id = 'test')
    {
        $captcha = \driphp\library\Captcha::factory();
        $captcha->flush(function ($code) {
            header("code: $code");
        });
    }
}