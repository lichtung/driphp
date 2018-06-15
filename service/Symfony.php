<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 11:28
 */
declare(strict_types=1);


namespace driphp\service;


use driphp\core\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class Symfony
 * @method Symfony getInstance(string $index = '') static
 * @package driphp\service\symfony
 */
class Symfony extends Service
{

    public function request(): Request
    {
        static $_instance = null;
        $_instance or $_instance = Request::createFromGlobals();
        return $_instance;
    }

    /**
     * @see http://symfony.com/doc/master/components/http_foundation/sessions.html
     * Symfony sessions are designed to replace several native PHP functions. Applications should avoid using session_start(), session_regenerate_id(), session_id(), session_name(), and session_destroy() and instead use the APIs in the following section.
     * @param bool $start
     * @return Session
     */
    public function session(bool $start = true): Session
    {
        static $_instance = null;
        if (null === $_instance) {
            $_instance = new Session();
            $start and $_instance->start();
        }
        return $_instance;
    }

}