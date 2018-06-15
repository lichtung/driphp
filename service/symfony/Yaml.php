<?php
/**
 * User: linzhv@qq.com
 * Date: 15/04/2018
 * Time: 12:31
 */
declare(strict_types=1);


namespace driphp\service\symfony;

use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as Y;
use driphp\core\Service;
use driphp\throws\io\FileNotFoundException;

/**
 * Class Yaml
 *
 * @see http://symfony.com/doc/current/components/yaml.html
 *
 * The Symfony Yaml component parses YAML strings to convert them to PHP arrays. It is also able to convert PHP arrays to YAML strings.
 * YAML, YAML Ain't Markup Language, is a human friendly data serialization standard for all programming languages.
 * YAML is a great format for your configuration files. YAML files are as expressive as XML files and as readable as INI files.
 *
 * 它的基本语法规则如下。
 *  - 大小写敏感
 *  - 使用缩进表示层级关系
 *  - 缩进时不允许使用Tab键，只允许使用空格。
 *  - 缩进的空格数目不重要，只要相同层级的元素左侧对齐即可
 *
 *
 * YAML 支持的数据结构有三种:
 *  - 对象：键值对的集合，又称为映射（mapping）/ 哈希（hashes） / 字典（dictionary）
 *  - 数组：一组按次序排列的值，又称为序列（sequence） / 列表（list）
 *  - 纯量 scalars：单个的、不可再分的值
 *
 * 对象
 *  animal: pets            =>  { animal: 'pets' }
 *  hash: { name: Steve, foo: bar }     => { hash: { name: 'Steve', foo: 'bar' } } # 行内对象
 * 数组
 *  - Cat
 *  - Dog
 *  - Goldfish              => [ 'Cat', 'Dog', 'Goldfish' ]
 *
 *  -
 *      - Cat
 *      - Dog
 *      - Goldfish => [ [ 'Cat', 'Dog', 'Goldfish' ] ]
 *
 *  - animal: [Cat, Dog]    => { animal: [ 'Cat', 'Dog' ] } # 行内数组
 * 纯量
 *  字符串     str: 这是一行字符串  # 字符串是最常见，也是最复杂的一种数据类型。字符串默认不使用引号表示
 *                              # 如果字符串之中包含空格或特殊字符，需要放在引号之中
 *                              # 单引号和双引号都可以使用，双引号不会对特殊字符转义(和PHP相反)
 *                              # s1: '内容\n字符串'
 *                                s2: "内容\n字符串" => { s1: '内容\\n字符串', s2: '内容\n字符串' }
 *                              # 单引号之中如果还有单引号，必须连续使用两个单引号转义  str: 'labor''s day'=>{ str: 'labor\'s day' }
 *                              # 字符串可以写成多行，从第二行开始，必须有一个单空格缩进。换行符会被转为空格
 *  布尔值     isSet: true
 *  整数
 *  浮点数     number: 12.30
 *  Null      parent: ~                     => { parent: null }
 *  时间      iso8601: 2001-12-14t21:59:43.10-05:00   => { iso8601: new Date('2001-12-14t21:59:43.10-05:00') }
 *  日期      date: 1976-07-31                => { date: new Date('1976-07-31') }
 *
 *
 * web_mode:
 * _protocol:_http
 * _host:_localhost
 * _port:_80
 * 上面“_”代表的是空格，正常解析结果：
 * array (
 *  'web_mode' =>
 *      array (
 *          'protocol' => 'http',
 *          'host' => 'localhost',
 *          'port' => 80,
 *      ),
 * )
 *
 *
 * The Symfony Yaml component is very simple and consists of two main classes: one parses YAML strings (Parser), and the other dumps a PHP array to a YAML string (Dumper).
 *
 *
 *
 * @method Yaml getInstance(string $index = '') static
 *
 *
 * @package driphp\service\symfony
 */
class Yaml extends Service
{
    # The YAML format supports two kind of representation for arrays, the expanded one, and the inline one.
    # By default, the dumper uses the expanded representation
    #
    # EXPANDED：
    #   foo: bar
    #   bar:
    #       foo: bar
    #       bar: baz
    #
    # INLINE：
    #   foo: bar
    #   bar: { foo: bar, bar: baz }
    #

    /**
     * parses a YAML string and converts it to a PHP array
     * e.g.
     *  "foo: bar"=> ['foo' => 'bar']
     *
     * @param string $yamlString
     * @return array
     * @throws ParseException If the YAML is not valid
     */
    public function parse(string $yamlString): array
    {
        return Y::parse($yamlString);
    }

    /**
     * @param string $yamlPath
     * @return array
     * @throws FileNotFoundException
     * @throws ParseException If the file could not be read or the YAML is not valid
     */
    public function parseFile(string $yamlPath): array
    {
        if (!is_file($yamlPath)) throw new FileNotFoundException($yamlPath);
        return self::parse(file_get_contents($yamlPath));
    }

    /**
     * @param array $data
     * @param int $inline The level where you switch to inline YAML
     * @return string
     * @throws DumpException If an error occurs during the dump, the parser throws a DumpException exception.
     */
    public function dump(array $data, int $inline = 1): string
    {
        return Y::dump($data, $inline);
    }
}