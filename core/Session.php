<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 16:27
 */
declare(strict_types=1);


namespace driphp\core;

use SessionHandlerInterface;
use driphp\Component;
use driphp\throws\ParametersInvalidException;

/**
 * Class Session 会话
 *
 * TODO 实现和Laravel一样的长久session机制
 *
 * @deprecated
 *
 * 获取session的状态(5.4)
 * php_SESSION_DISABLED if sessions are disabled.
 * php_SESSION_NONE if sessions are enabled, but none exists.
 * php_SESSION_ACTIVE if sessions are enabled, and one or more exists.
 *
 * 不再使用的函数列表：
 * ①session_is_registered
 * ②session_register
 * ③session_unregister
 *
 * @package driphp\core
 */
class Session extends Component
{
    protected function initialize()
    {
    }

    /**
     * 客户端缓存控制策略
     * 客户端或者代理服务器通过检测这个响应头信息来 确定对于页面内容的缓存规则
     * nocache 会禁止客户端或者代理服务器缓存内容
     * public 表示允许客户端或代理服务器缓存内容
     * private 表示允许客户端缓存， 但是不允许代理服务器缓存内容
     * private 模式下， 包括 Mozilla 在内的一些浏览器可能无法正确处理 Expire 响应头， 通过使用 private_no_expire 模式可以解决这个问题：在这种模式下， 不会向客户端发送 Expire 响应头
     */
    /**
     * Expires: Thu, 19 Nov 1981 08:52:00 GMT
     * Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
     * Pragma: no-cache
     */
    const LIMITER_NOCACHE = 'nocache';
    /**
     * Expires：（根据 session.cache_expire 的设定计算得出）
     * Cache-Control： public, max-age=（根据 session.cache_expire 的设定计算得出）
     * Last-Modified：（会话最后保存时间）
     */
    const LIMITER_PUBLIC = 'public';
    /**
     * Expires: Thu, 19 Nov 1981 08:52:00 GMT
     * Cache-Control: private, max-age=（根据 session.cache_expire 的设定计算得出）,
     *      pre-check=（根据 session.cache_expire 的设定计算得出）
     * Last-Modified: （会话最后保存时间）
     */
    const LIMITER_PRIVATE = 'private';
    /**
     * Cache-Control: private, max-age=（根据 session.cache_expire 的设定计算得出）,
     *      pre-check=（根据 session.cache_expire 的设定计算得出）
     * Last-Modified: （会话最后保存时间）
     */
    const LIMITER_PRIVATE_WITHOUT_EXPIRE = 'private_no_expire';


    /**
     * 获取 或者 设置当前缓存的到期时间
     * 注意需要在session_start之前调用才有效
     * @param string|null $new_cache_expire 新的到期时间，单位为分钟，如果为null表示获取
     * @return bool|int false时表示设置失败
     */
    public function cacheExpire($new_cache_expire = null)
    {
        if (isset($new_cache_expire)) {

        } else {
            if ('nocache' === ini_get('session.cache_limiter')) {
                return false;
            }
            return session_cache_expire('session.cache_expire');
        }
        return session_cache_expire();
    }

    /**
     * 获取和设置当前缓存限制器的名称
     * 缓存限制器定义了向客户端发送的 HTTP 响应头中的缓存控制策略，
     * 客户端或者代理服务器通过检测这个响应头信息来 确定对于页面内容的缓存规则
     *
     * 设置缓存限制器：
     * ①nocache 会进制客户端或者代理服务器缓存内容
     * ②public 表示允许客户端或代理服务器缓存内容
     * ③private 表示允许客户端缓存， 但是不允许代理服务器缓存内容
     * ④private_no_expire 模式可以解决在 private 模式下， 包括 Mozilla 在内的一些浏览器可能无法正确处理 Expire 响应头的这类问题：
     *  在这种模式下， 不会向客户端发送 Expire 响应头
     *
     * 原文：session_cache_limiter() returns the name of the current cache limiter.
     * If cache_limiter is specified, the name of the current cache limiter is changed to the new value.
     * The cache limiter defines which cache control HTTP headers are sent to the client.
     * These headers determine the rules by which the page content may be cached by the client and intermediate proxies.
     * Setting the cache limiter to nocache disallows any client/proxy caching.
     * A value of public permits caching by proxies and the client,
     * whereas private disallows caching by proxies and permits the client to cache the contents.
     * In private mode, the Expire header sent to the client may cause confusion for some browsers, including Mozilla.
     * You can avoid this problem by using private_no_expire mode.
     * The expire header is never sent to the client in this mode.
     *
     * 注意需要在session_start之前调用才有效
     * @param null|string $cache_limiter 为null时获取当前缓存限制器名称
     * @return string
     */
    public static function cacheLimiter($cache_limiter = null)
    {
        if (null === $cache_limiter) {
            return session_cache_limiter();
        }
        return session_cache_limiter($cache_limiter);
    }

    /**
     * 读取/设置会话名称
     * 用在 cookie 或者 URL 中的会话名称， 例如：phpSESSID。
     * 只能使用字母和数字作为会话名称，建议尽可能的短一些，
     * 并且是望文知意的名字（对于启用了 cookie 警告的用户来说，方便其判断是否要允许此 cookie）。
     * 如果指定了 name 参数， 那么当前会话也会使用指定值作为名称
     * @param string $newname null时返回当前的session名称，否则设置并返回之前的名称
     * @return string
     */
    public static function name($newname = null)
    {
        return session_name($newname);
    }

    /**
     * Session data is usually stored after your script terminated without the need to call session_write_close()
     * session数据通常在脚本执行结束后存储，而不需要调用函数session_write_close
     * but as session data is locked to prevent concurrent writes only one script may operate on a session at any time
     * 但是由于为阻止并行的写入session数据会被上锁，其结果是任何时候只有一个脚本才能操作一个session
     * When using framesets together with sessions you will experience the frames loading one by one due to this locking
     * 浏览器中使用frameset和session的时候，你会经历到frame会逐一加载frame，这归因于此
     * You can reduce the time needed to load all the frames by ending the session as soon as all changes to session variables are done.
     * 你可以减少所有的frame的加载时间，通过当session数据操作完成后尽快结束session的方式
     * @return void
     */
    public static function commit()
    {
        session_write_close();
    }

    /**
     * 返回当前会话编码后的数据，即$_SESSION
     * 请注意，序列方法 和 serialize()  是不一样的。 该序列方法是内置于 php 的，能够通过设置 session.serialize_handler 来设置。
     * @return string
     */
    public static function getEncodedSessionData()
    {
        return session_encode();
    }

    /**
     * 对参数进行session解码，并填充到$_SESSION变量中
     * 请注意，序列方法 和 serialize()  是不一样的。 该序列方法是内置于 php 的，能够通过设置 session.serialize_handler 来设置。
     * @param string $code_data 待解码的数据
     * @return bool
     */
    public static function decode($code_data)
    {
        return session_decode($code_data);
    }

    /**
     * session_reset()  reinitializes a session with original values stored in session storage.
     * This function requires active session and discards changes in $_SESSION.
     * 重置session的改动，恢复到最初的状态
     * @return void
     */
    public static function reset()
    {
        session_reset();
    }

    /**
     * 获取和设置session的保存路径
     * 在某些操作系统上，建议使用可以高效处理 大量小尺寸文件的文件系统上的路径来保存会话数据。
     * 例如，在 Linux 平台上，对于会话数据保存的工作而言，reiserfs 文件系统会比 ext2fs 文件系统能够提供更好的性能。
     *
     * 必须在调用开始以会话之前调用该函数 即在调用session_start() 函数之前调用 session_save_path() 函数
     * @param string|null $path 参数为null时获取保存路径
     * @return string
     */
    public static function savePath($path = null)
    {
        return session_save_path($path);//读取源码发现session_save_path的默认参数为null
    }

    /**
     * 开启会话
     * 必须在脚本输出之前调用
     * @return bool
     */
    public static function begin()
    {
        self::hasStarted() or session_start();
        return true;
    }

    /**
     * 判断Session是否开启
     * @return bool
     */
    public static function hasStarted()
    {
        if (headers_sent()) {
            ob_get_level() and ob_end_clean();
        }
        return function_exists('session_status') ? (PHP_SESSION_ACTIVE == session_status()) : ('' === session_id());
    }

    /**
     * @return void
     */
    public static function pause()
    {
        session_write_close();
    }

    /**
     * 销毁会话中全部数据
     * 要想重新使用session，需要重新调用session_start函数
     * 注意：unset($_SESSION)会导致$_SESSION数组彻底地不能使用,调用session_unset可以释放所有的注册的session变量
     * @return bool
     */
    public static function destroy()
    {
        session_unset();
        return session_destroy();
    }

    /**
     * sessionID操作
     * @param string|null $id 设置的sessionID
     * @param bool|false $regenerate 是否重新生成sessionID
     * @return string
     */
    public static function id($id = null, $regenerate = false)
    {
        $regenerate and session_regenerate_id();
        return session_id($id);
    }

    /**
     * 要求php版本在5.4之后才能使用
     *  设置用户自定义会话存储处理类（版本5.4以后使用）
     * @param SessionHandlerInterface $session_handler 实现了 SessionHandlerInterface 接口的对象,例如 SessionHandler
     * @param bool|true $register_shutdown 将函数 session_write_close() 注册为 register_shutdown_function() 函数
     *                                     默认为true表示session自动在脚本执行结束的时候调用
     * @return bool
     */
    public static function setSaveHandler(SessionHandlerInterface $session_handler, $register_shutdown = true)
    {
        return @session_set_save_handler($session_handler, $register_shutdown);
    }

    /**
     * 获取/设置会话 cookie 参数
     * 返回数组 array(
     *      "lifetime",// - cookie 的生命周期，以秒为单位。
     *      "path",// - cookie 的访问路径。
     *      "domain",// - cookie 的域。
     *      "secure",// - 仅在使用安全连接时发送 cookie。
     *      "httponly",// - 只能通过 http 协议访问 cookie
     * )
     * 以下方法等效
     * ini_get('session.cookie_lifetime'),
     * ini_get('session.cookie_path'),
     * ini_get('session.cookie_domain'),
     * ini_get('session.cookie_secure'),
     * ini_get('session.cookie_httponly'),
     *      <==>
     * session_get_cookie_params()
     * @param array $params cookie参数设置
     * @return mixed
     */
    public static function cookieParams($params = null)
    {
        if (isset($params)) {
            session_set_cookie_params(
                $params[0],
                isset($params[1]) ? $params[1] : null,
                isset($params[2]) ? $params[2] : null,
                isset($params[3]) ? $params[3] : false,
                isset($params[4]) ? $params[4] : false
            );
        }
        return session_get_cookie_params();
    }

    /**
     * 检查是否设置了指定名称的session
     * @param string $name
     * @return bool
     */
    public static function has($name)
    {
        self::begin();
        if (strpos($name, '.')) { // 支持数组
            list($name1, $name2) = explode('.', $name);
            return isset($_SESSION[$name1][$name2]);
        } else {
            return isset($_SESSION[$name]);
        }
    }


    /**
     * 删除所有session
     * @return void
     */
    public static function clear()
    {
        self::begin();
        $_SESSION = [];
    }

    /**
     * 清除指定名称的session
     * @param string|array $name 如果为null将清空全部
     * @return bool
     * @throws ParametersInvalidException
     */
    public static function delete($name)
    {
        self::begin();
        if (!empty($_SESSION) and $name) {
            if (is_string($name)) {
                if (strpos($name, '.')) {
                    list($name1, $name2) = explode('.', $name);
                    unset($_SESSION[$name1][$name2]);
                } else {
                    unset($_SESSION[$name]);
                }
            } elseif (is_array($name)) {
                foreach ($name as $val) {
                    self::delete($val);
                }
            } else {
                throw new ParametersInvalidException($name);
            }
        }
        return false;
    }

    /**
     * 当上一次请求的时间间隔大于参数一的时候进行回调
     * @param int $spacing 间隔事件，以秒计
     * @param callable $call
     * @return mixed
     */
    public static function waitOn($spacing, callable $call)
    {
        $lasttime = Session::get('last_request_time', false);
        if ($lasttime) {
            $time = time() - $lasttime;
            if ($time <= $spacing) {
                sleep($spacing - $time + 1);
            }
        }
        $content = $call();
        Session::set('last_request_time', time());
        return $content;
    }


    /**
     * 设置session
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set($name, $value)
    {
        self::begin();
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name, 2);
            $_SESSION[$name1][$name2] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
    }

    /**
     * 获取指定名称的session的值
     * @param null|string $name 为null时获取全部session
     * @param mixed $replacement 未找到指定的session时返回的代替值，默认为null
     * @return mixed return null if not set
     */
    public static function get($name = null, $replacement = null)
    {
        self::begin();
        if (isset($name)) {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : $replacement;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : $replacement;
            }
        }
        return $_SESSION;
    }

}