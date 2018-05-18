<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 16:23
 */

namespace sharin\library;


/**
 * Class Config
 * @package sharin\library
 * @deprecated
 */
class Config
{
    /**
     * 配置类型
     * 值使用字符串而不是效率更高的数字是处于可以直接匹配后缀名的考虑
     */
    const TYPE_PHP = 'php';
    const TYPE_INC = 'inc';
    const TYPE_INI = 'ini';//Initialization File - 初始化文件,是windows的系统配置文件所采用的存储格式，统管windows的各项配置
    const TYPE_YAML = 'yaml';
    const TYPE_XML = 'xml';
    const TYPE_JSON = 'json';
    const TYPE_SERIAL = 'serial';

    /**
     * 保存配置
     * @param string $path 配置文件路径
     * @param array $data
     * @param string $type
     * @return bool
     * @throws SharinException 配置出错时抛出
     */
    public static function store(string $path, array $data, string $type = self::TYPE_PHP): bool
    {
        $type or $type = pathinfo($path, PATHINFO_EXTENSION);
        # 检查上级目录目录
        is_dir($dirname = dirname($path)) or mkdir($dirname, 0777, true);
        switch ($type) {
            case self::TYPE_PHP:
            case self::TYPE_INC:
                $res = file_put_contents($path, '<?php defined(\'IN_VERSION\') or die(\'No Permission\'); return ' . var_export($data, true) . ';');
                break;
            case self::TYPE_INI:
                $res = file_put_contents($path, Inier::create($data));
                break;
            case self::TYPE_YAML:
                $res = yaml_emit_file($path, $data, YAML_UTF8_ENCODING);
                break;
            case self::TYPE_XML:
                $res = file_put_contents($path, Xml::arrayToXml($data));
                break;
            case self::TYPE_JSON:
                $res = file_put_contents($path, json_encode($data));
                break;
            case self::TYPE_SERIAL:
                $res = file_put_contents($path, serialize($data));
                break;
            default :
                throw new SharinException("bad config type '$type'");
        }
        return $res !== false;
    }

    /**
     * 解析配置文件爱呢
     * @param string $path 配置文件的路径
     * @param string|null $type 配置文件的类型,参数为null时根据文件名称后缀自动获取
     * @param callable $parser 配置解析方法 有些格式需要用户自己解析
     * @return array
     * @throws SharinException 配置出错时抛出
     */
    public static function parse($path, $type = '', callable $parser = null)
    {
        if (!is_readable($path)) return [];//文件不存在或者不可以读取
        $type or $type = pathinfo($path, PATHINFO_EXTENSION);
        switch ($type) {
            case self::TYPE_INC:
            case self::TYPE_PHP:
                $result = is_file($path) ? include $path : [];
                break;
            case self::TYPE_INI:
                return Inier::parse($path);
                break;
            case self::TYPE_YAML:
                $result = yaml_parse_file($path);
                break;
            case self::TYPE_XML:
                $result = (array)simplexml_load_file($path);
                break;
            case self::TYPE_JSON:
                $result = json_decode(file_get_contents($path), true);
                break;
            case self::TYPE_SERIAL:
                $content = file_get_contents($path);
                $result = unserialize($content);
                break;
            default:
                if ($parser) {
                    $result = $parser($path);
                } else {
                    throw new SharinException("bad config type '$type'");
                }
        }
        return is_array($result) ? $result : [];
    }
}