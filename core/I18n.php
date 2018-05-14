<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 16:02
 */
declare(strict_types=1);


namespace sharin\core;


use sharin\Component;
use sharin\Kernel;

/**
 * Class I18n
 *
 * Internationalization - i18n
 *
 * zh       -   中文
 * zh_CN    -   中文（简体）
 * zh_TW    -   中文（繁体）
 * zh_HK    -   中文（香港）
 *
 * @method I18n getInstance(array $config = []) static
 *
 * @package sharin\core
 */
class I18n extends Component
{

    /**
     * @var string 实例代表的语言
     */
    private $lang = '';


    protected $config = [
        'default_lang' => 'en'
    ];

    /**
     * @return $this|void
     * @throws \sharin\SharinException
     * @throws \sharin\throws\io\FileWriteException
     */
    protected function initialize()
    {
        $this->lang = Request::getInstance()->language() ?? $this->config['default_lang'];
        $this->load(false);
    }


    /**
     * @var array
     */
    private $cache = [];

    /**
     * get all language map stored in map
     * @return array
     */
    public function all(): array
    {
        return $this->cache;
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key): string
    {
        return $this->cache[$key] ?? $key;
    }

    /**
     * # format
     * # ...
     * @param array ...$args
     * @return string
     */
    public function format(...$args): string
    {
        $format = array_shift($args);
        array_unshift($args, $this->cache[$format] ?? $format);
        return call_user_func_array('sprintf', $args);
    }

    /**
     * @param bool $rebuild
     * @return array
     * @throws \sharin\SharinException
     * @throws \sharin\throws\io\FileWriteException
     */
    public function load(bool $rebuild = false): array
    {
        $relativePath = "i18n/{$this->lang}.php";
        $cacheFile = SR_PATH_RUNTIME . $relativePath;
        # It will rebuild cache if force to do or cache file not exist or cache expired
        if ($rebuild or !is_file($cacheFile) or (time() > filemtime($cacheFile) + 60)) {
            # load framework language pack
            $this->cache = is_file($innerPath = SR_PATH_FRAMEWORK . $relativePath) ? Kernel::configuration($innerPath) : [];
            # load project language pack
            if (is_file($outerPath = SR_PATH_PROJECT . $relativePath)) {
                $outer = include($outerPath);
                $outer and $this->cache = array_merge($this->cache, $outer);
            }
            Kernel::configuration(SR_PATH_RUNTIME . $relativePath, $this->cache);
        } else {
            $this->cache = Kernel::configuration($cacheFile);
        }

        return $this->cache;
    }

}