<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:35
 */

namespace driphp\library;

use driphp\throws\ConfigInvalidException;

/**
 * Class Config 配置
 * @package driphp\library
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
     * @throws ConfigInvalidException 配置出错时抛出
     */
    public static function store(string $path, array $data, string $type = self::TYPE_PHP): bool
    {
        $type or $type = pathinfo($path, PATHINFO_EXTENSION);
        # 检查上级目录目录
        is_dir($dirName = dirname($path)) or mkdir($dirName, 0777, true);
        switch ($type) {
            case self::TYPE_PHP:
            case self::TYPE_INC:
                $res = file_put_contents($path, '<?php defined(\'IN_VERSION\') or die(\'No Permission\'); return ' . var_export($data, true) . ';');
                break;
            case self::TYPE_INI:
                $res = file_put_contents($path, self::createIni($data));
                break;
            case self::TYPE_YAML:
                $res = yaml_emit_file($path, $data, YAML_UTF8_ENCODING);
                break;
            case self::TYPE_XML:
                $res = file_put_contents($path, self::arrayToXml($data));
                break;
            case self::TYPE_JSON:
                $res = file_put_contents($path, json_encode($data));
                break;
            case self::TYPE_SERIAL:
                $res = file_put_contents($path, serialize($data));
                break;
            default :
                throw new ConfigInvalidException("bad config type '$type'");
        }
        return $res !== false;
    }

    /**
     * 解析配置文件爱呢
     * @param string $path 配置文件的路径
     * @param string|null $type 配置文件的类型,参数为null时根据文件名称后缀自动获取
     * @param callable $parser 配置解析方法 有些格式需要用户自己解析
     * @return array
     * @throws ConfigInvalidException 配置出错时抛出
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
                return self::parseIni($path);
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
                    throw new ConfigInvalidException("bad config type '$type'");
                }
        }
        return is_array($result) ? $result : [];
    }

    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 解析INI文件或者字符串并返回数组
     * @param string $ini ini文件或者ini字符串
     * @param bool $isFile 参数一是否是ini文件
     * @return array 解析结果
     */
    public static function parseIni(string $ini, bool $isFile = true): array
    {
        $temp = $isFile ? parse_ini_file($ini) : parse_ini_string($ini);
        $result = [];
        if ($temp) foreach ($temp as $name => $value) {
            if (strpos($name, '.')) {
                $name = explode('.', $name);
                $lastIndex = count($name) - 1;
                $target = &$result;
                foreach ($name as $i => $nm) {
                    if ($i === $lastIndex) {
                        //还没到最后一个
                        $target[$nm] = $value;
                        break;
                    } else {
                        isset($target[$nm]) or $target[$nm] = [];
                        $target = &$target[$nm];
                    }
                }
            } else {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * 创建ini配置文件
     * @param array $data
     * @param string $parent
     * @return string
     */
    public static function createIni(array $data, string $parent = ''): string
    {
        $str = '';
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                //如果遇到数字键，则为一个段落section分隔
                $str .= "[{$value}]" . PHP_EOL;
            } else {
                $key = $parent ? "{$parent}.{$key}" : $key;
                if (is_array($value)) {
                    $str .= self::createIni($value, $key);
                } else {
                    $str .= "{$key} = {$value}" . PHP_EOL;
                }
            }
        }
        return $str;
    }

}