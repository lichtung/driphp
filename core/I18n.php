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
    protected $config = [
        'default_lang' => 'en'
    ];

    private $lang = '';
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @return Component|void
     * @throws \sharin\SharinException
     * @throws \sharin\throws\io\FileWriteException
     */
    protected function initialize()
    {
        $this->lang = $this->config['default_lang'] ?? 'en';
        $this->cache = $this->load();
    }

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
     * @return array
     * @throws \sharin\SharinException
     */
    public function load(): array
    {
        static $_languages = [];
        if (!isset($_languages[$this->lang])) {
            $relativePath = "i18n/{$this->lang}.php";
            $innerLang = Kernel::readConfig(SR_PATH_FRAMEWORK . $relativePath);
            if (is_file($outerPath = SR_PATH_PROJECT . $relativePath)) {
                $outerLang = include($outerPath);
                $_languages[$this->lang] = array_merge($innerLang, $outerLang);
            } else {
                $_languages[$this->lang] = $innerLang;
            }
        }
        return $_languages[$this->lang];
    }

}