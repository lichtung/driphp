<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 15:55
 */
declare(strict_types=1);


namespace sharin\library;


class XMLer
{


    /**
     * Convert Reserved XML characters to Entities
     * 将XML标签转换成实体以避免被浏览器解析成标签而实际输出原始XML字符串
     *
     * Takes a string as input and converts the following reserved XML characters to entities
     *  ①Ampersands: &
     *  ②Less than and greater than characters: < >
     *  ③Single and double quotes: ‘ “
     *  ④Dashes: -
     *
     * This function ignores ampersands if they are part of existing numbered character entities, e.g. &#123;.
     *
     * <code>
     *  echo '<p>Here is a paragraph & an entity ------ (&#123;).</p><br />';
     *  echo XMLHelper::convert('<p>Here is a paragraph & an entity ------ (&#123;).</p><br />');
     *
     *  // &lt;p&gt;Here is a paragraph &amp; an entity &#45;&#45;&#45;&#45;&#45;&#45; (&#123;).&lt;/p&gt;&lt;br /&gt;
     *  //于是浏览器上能显示为：<p>Here is a paragraph & an entity ------ ({).</p><br />
     *  //如果未转换则直接显示为：Here is a paragraph & an entity ------ ({).
     * </code>
     *
     * @param string $str the text string to convert
     * @param bool|FALSE $protect_all Whether to protect all content that looks like a potential entity instead of just numbered entities, e.g. &foo;
     * @return string XML-converted string
     */
    public static function convert($str, $protect_all = false)
    {
        $temp = '__TEMP_AMPERSANDS__';

        // Replace entities to temporary markers so that
        // ampersands won't get messed up
        $str = preg_replace('/&#(\d+);/', $temp . '\\1;', $str);

        if ($protect_all === TRUE) {
            $str = preg_replace('/&(\w+);/', $temp . '\\1;', $str);
        }

        $str = str_replace(
            array('&', '<', '>', '"', "'", '-'),
            array('&amp;', '&lt;', '&gt;', '&quot;', '&apos;', '&#45;'),
            $str
        );

        // Decode the temp markers back to entities
        $str = preg_replace('/' . $temp . '(\d+);/', '&#\\1;', $str);

        if ($protect_all === TRUE) {
            return preg_replace('/' . $temp . '(\w+);/', '&\\1;', $str);
        }

        return $str;
    }

    public static function parseAttrs(string $attrs): array
    {
        $xml = '<tpl><tag ' . $attrs . ' /></tpl>';
        $xml = simplexml_load_string($xml);
        if (!$xml) return [];
        $xml = (array)($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        return $array;
    }

    /**
     * 删除指定的标签和内容
     * @param array $tags 需要删除的标签数组
     * @param string $str 数据源
     * @param bool $save_content 是否删除标签内的内容 0保留内容 1不保留内容
     * @return string
     */
    public static function stripHtmlTags($tags, $str, $save_content = false)
    {
        $html = array();
        if ($save_content) {
            foreach ($tags as $tag) {
                $html[] = '/(<' . $tag . '.*?>[\s|\S]*?<\/' . $tag . '>)/';
            }
        } else {
            foreach ($tags as $tag) {
                $html[] = '/(<(?:\/' . $tag . '|' . $tag . ')[^>]*>)/i';
            }
        }
        return preg_replace($html, '', $str);
    }


    /**
     * 字符串截取，支持中文和其他编码
     * @param string $str 需要转换的字符串
     * @param int $start 开始位置
     * @param string $length 截取长度
     * @param bool $suffix 截断显示字符
     * @param string $charset 编码格式
     * @return string
     */
    function reSubstr($str, $start = 0, $length, $suffix = true, $charset = "utf-8")
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join('', array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice . '...' : $slice;
    }


    /**
     * 数组转XML
     * @param array $arr
     * @return string
     */
    public static function arrayToXml(array $arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<{$key}>{$val}</{$key}>";
            } else {
                $xml .= "<{$key}><![CDATA[{$val}]]></{$key}>";
            }
        }
        return $xml . '</xml>';
    }

    public static function parseWechatXml($xml)
    {
        return (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * 将XML转为array
     * @param string $xml
     * @return array
     */
    public static function xml2Array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * 去掉HTML代码中的HTML标签，返回纯文本
     * @param string $document 待处理的字符串
     * @return string
     */
    public static function html2txt($document)
    {
        $search = array("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
            "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
            "'([\r\n])[\s]+'", // 去掉空白字符
            "'&(quot|#34);'i", // 替换 HTML 实体
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&#(\d+);'e"); // 作为 PHP 代码运行
        $replace = array("",
            "",
            "",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            "chr(\\1)");
        $text = preg_replace($search, $replace, $document);
        return $text;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string|array $attrs 根节点属性
     * @param string $id 数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public static function encode($data, $root = 'root', $item = 'item', $attrs = '', $id = 'id', $encoding = 'utf-8')
    {
        if (!$attrs) {
            if (is_array($attrs)) {
                $_attr = array();
                foreach ($attrs as $key => $value) {
                    $_attr[] = "{$key}=\"{$value}\"";
                }
                $attrs = implode(' ', $_attr);
            }
        } else {
            $attrs = '';
        }

        $inner = self::data2Xml($data, $item, $id);
        return "<?xml version=\"1.0\" encoding=\"{$encoding}\"?><{$root} {$attrs}>{$inner}</{$root}>";
    }

    /**
     * TP3.2.3
     *
     * 数据XML编码
     * @param mixed $data 数据
     * @param string $item 数字索引时的节点名称
     * @param string $id 数字索引key转换为的属性名
     * @return string
     */
    public static function data2Xml($data, $item = 'item', $id = 'id'): string
    {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id and $attr = " {$id}=\"{$key}\"";
                $key = $item;
            }
            $content = (is_array($val) || is_object($val)) ? self::data2Xml($val, $item, $id) : $val;
            $xml .= "<{$key}{$attr}>{$content}</{$key}>";
        }
        return $xml;
    }

}