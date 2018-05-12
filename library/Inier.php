<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 15:59
 */
declare(strict_types=1);


namespace sharin\library;


class Inier
{
    /**
     * 解析INI文件或者字符串并返回数组
     * @param string $ini ini文件或者ini字符串
     * @param bool $isFile 参数一是否是ini文件
     * @return array 解析结果
     */
    public static function parse(string $ini, bool $isFile = true): array
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
    public static function create(array $data, string $parent = ''): string
    {
        $str = '';
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                //如果遇到数字键，则为一个段落section分隔
                $str .= "[{$value}]" . PHP_EOL;
            } else {
                $key = $parent ? "{$parent}.{$key}" : $key;
                if (is_array($value)) {
                    $str .= self::create($value, $key);
                } else {
                    $str .= "{$key} = {$value}" . PHP_EOL;
                }
            }
        }
        return $str;
    }
}