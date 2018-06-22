<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/20 0020
 * Time: 21:21
 */
declare(strict_types=1);


namespace driphp\core\view;

use driphp\core\FileSystem;
use driphp\core\Request;
use driphp\Kernel;
use driphp\library\CodeFactory;
use driphp\DriException;
use driphp\throws\io\FileNotFoundException;

/**
 * Class Think  Template engine from thinkphp3.2.3
 * @package driphp\core\view
 */
class Think
{
    /**
     * @var array
     */
    protected $_context = null;

    /**
     * @var array
     */
    protected $_tVars = [];
    protected $templateFile = '';
    public $tVar = array();

    /**
     * 标签库定义XML文件
     * @var string
     * @access protected
     */
    protected $xml = '';
    // 标签定义
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'volist' => ['attr' => 'name,id,offset,length,key,mod', 'level' => 3, 'alias' => 'iterate'],
        'articles' => ['attr' => 'sql,param,empty,error,table,fields,where,limit,index', 'level' => 3],//sql="select * from user where name=:name and age=:age;" param="name,age" empty="" error=""
        'foreach' => ['attr' => 'name,item,key', 'level' => 3],
        'if' => ['attr' => 'condition', 'level' => 2],
        'elseif' => ['attr' => 'condition', 'close' => 0],
        'else' => ['attr' => '', 'close' => 0],
        'switch' => ['attr' => 'name', 'level' => 2],
        'case' => ['attr' => 'value,break'],
        'default' => ['attr' => '', 'close' => 0],
        'compare' => ['attr' => 'name,value,type', 'level' => 3, 'alias' => 'eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq'],
        'range' => ['attr' => 'name,value,type', 'level' => 3, 'alias' => 'in,notin,between,notbetween'],
        'empty' => ['attr' => 'name', 'level' => 3],
        'notempty' => ['attr' => 'name', 'level' => 3],
        'present' => ['attr' => 'name', 'level' => 3],
        'notpresent' => ['attr' => 'name', 'level' => 3],
        'defined' => ['attr' => 'name', 'level' => 3],
        'notdefined' => ['attr' => 'name', 'level' => 3],
        'assign' => ['attr' => 'name,value', 'close' => 0],
        'define' => ['attr' => 'name,value', 'close' => 0],
        'for' => ['attr' => 'start,end,name,comparison,step', 'level' => 3],
    ];

    /**
     * 配置
     * @var array
     */
    private $config = null;
    private $literal = array();
    private $block = array();

    /**
     * Think constructor.
     * @access public
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        $this->config = [
            'CACHE_ON' => true,//缓存是否开启
            'CACHE_EXPIRE' => 10,//缓存时间，0便是永久缓存,仅以设置为30
            'CACHE_UPDATE_CHECK' => true,//是否检查模板文件是否发生了修改，如果发生修改将更新缓存文件（实现：检测模板文件的时间是否大于缓存文件的修改时间）

            'CACHE_PATH' => DRI_PATH_RUNTIME . '/tpview/',
            'TEMPLATE_SUFFIX' => '.php',     // 默认模板文件后缀
            'CACHFILE_SUFFIX' => '.php',      // 默认模板缓存后缀
            'TAGLIB_BEGIN' => '<',  // 标签库标签开始标记
            'TAGLIB_END' => '>',  // 标签库标签结束标记
            'L_DELIM' => '{',            // 模板引擎普通标签开始标记
            'R_DELIM' => '}',            // 模板引擎普通标签结束标记
//        'DENY_FUNC_LIST'   =>  'echo,exit',    // 模板引擎禁用函数
            'VAR_IDENTIFY' => 'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象

            'TMPL_PARSE_STRING' => [],//用户自定义的字符替换
        ];
        $config and $this->config = array_merge($this->config, $config);
        $this->config['TAGLIB_BEGIN'] = $this->stripPreg($this->config['TAGLIB_BEGIN']);
        $this->config['TAGLIB_END'] = $this->stripPreg($this->config['TAGLIB_END']);
        $this->config['L_DELIM'] = $this->stripPreg($this->config['L_DELIM']);
        $this->config['R_DELIM'] = $this->stripPreg($this->config['R_DELIM']);
    }

    /**
     * 让模板引擎知道调用的相关上下文环境
     * @param array $context 上下文环境，包括模块、控制器、方法和模板信息可供设置使用
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->_context = $context;
        return $this;
    }

    /**
     * 保存控制器分配的变量
     * @param string|array $name
     * @param null $value
     * @return $this
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->_tVars = array_merge($this->_tVars, $name);
        } else {
            $this->_tVars[$name] = $value;
        }
        return $this;
    }

    /**
     * 替换字符串
     * @var array
     */
    protected $_replacement = [
    ];

    /**
     * 设置模板替换字符串
     * @param string $str
     * @param string $replacement
     * @return void
     */
    public function registerParsingString($str, $replacement)
    {
        $this->_replacement[$str] = $replacement;
    }

    /**
     * 获取所有替换字符串
     * @return array
     */
    public function getParsingString()
    {
        return $this->_replacement;
    }

    /**
     * 显示模板
     * @param array $context
     * @return void
     */
    public function display($context = null)
    {
        $template = self::parseTemplatePath($context);
        $this->_context = $context;
        //模板常量
        $this->_replacement['__PUBLIC__'] = $_public_url = Request::getInstance()->getPublicUrl();

        $this->_replacement['__ROOT__'] = $_public_url; // 当前网站地址,带脚本名称
        $this->_replacement['__MODULE__'] = $_public_url . '/' . $context['m'];
        $this->_replacement['__CONTROLLER__'] = $_public_url . "/{$context['m']}/{$context['c']}";
        $this->_replacement['__ACTION__'] = $_public_url . "/{$context['m']}/{$context['c']}/{$context['a']}";

        //如果原先的后缀名是.php 则这里的操作可以避免修改，如果是标准的框架内的方式如$this->display('index')
        //或者$this->display(/* 方法名称自动获取 */)，则一定会进入到else中并连接上预定义的后缀名
        if (strpos($template, '.')) {
            //存在.表示已经设置了后缀名
        } else {
            //连接上默认的后缀名
            $template .= $this->config['TEMPLATE_SUFFIX'];
        }

        $this->fetch($this->template = $template, $this->_tVars);
    }

    protected $template = null;

//----------------------------------------- 修改自ThinkPHP --------------------------------------------------------------------------//

    /**
     * 字符串替换 避免正则混淆
     * @access private
     * @param string $str
     * @return string
     */
    private function stripPreg($str)
    {
        return str_replace(
            ['{', '}', '(', ')', '|', '[', ']', '-', '+', '*', '.', '^', '?'],
            ['\{', '\}', '\(', '\)', '\|', '\[', '\]', '\-', '\+', '\*', '\.', '\^', '\?'],
            $str);
    }

    /**
     * 模板变量获取
     * @param $name
     * @return bool|mixed
     */
    public function get($name)
    {
        if (isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    /**
     * 模板变量设置
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->tVar[$name] = $value;
    }

    /**
     * 加载模板
     * @access public
     * @param string $templateFile 模板文件
     * @param array $templateVar 模板变量
     * @param string $prefix 模板标识前缀
     * @return void
     */
    /**
     * @param $templateFile
     * @param $templateVar
     * @param string $prefix
     * @return void
     */
    public function fetch($templateFile, $templateVar, $prefix = '')
    {
        $this->tVar = $templateVar;
        $cachefile = $this->template($templateFile, $prefix);
        Kernel::template($cachefile, $this->tVar, false);
    }

    /**
     * 加载主模板并缓存
     * @access public
     * @param string $templateFile 模板文件
     * @param string $prefix 模板标识前缀
     * @return string
     * @throws
     */
    public function template($templateFile, $prefix = '')
    {
        //确定缓存文件名称
        $tmpl = $this->config['CACHE_PATH'];
        $tmpl .= isset($this->_context['m']) ? $this->_context['m'] . '/' : 'UModule/';
        $tmpl .= isset($this->_context['c']) ? $this->_context['c'] . '/' : 'UController/';
        $tmpl .= $prefix . (isset($this->_context['a']) ? $this->_context['a'] : md5($templateFile)) . $this->config['CACHFILE_SUFFIX'];

        if ($this->config['CACHE_ON'] and $this->config['CACHE_EXPIRE'] > 0 and is_file($tmpl)) {
            $lastmtime = FileSystem::mtime($tmpl);
            //缓存开启并且缓存文件存在的情况下价差缓存是否过期
            if ($lastmtime + $this->config['CACHE_EXPIRE'] > $_SERVER['REQUEST_TIME']) {//缓存期未结束
                if ($this->config['CACHE_UPDATE_CHECK']) {
                    if (FileSystem::mtime($templateFile) < $lastmtime) {//模板文件更新
                        return $tmpl;
                    }
                } else {
                    return $tmpl;
                }
            }
        }

        if (is_file($templateFile)) {
            $this->templateFile = $templateFile;
            // 读取模板文件内容
            $tmplContent = FileSystem::read($templateFile);//file_get_contents($templateFile);
        } else {
            throw new FileNotFoundException($templateFile);
        }
        // 编译模板内容
        $tmplContent = $this->compiler($tmplContent);
        FileSystem::write($tmpl, trim($tmplContent));
        return $tmpl;
    }

    /**
     * 编译模板文件内容
     * @access protected
     * @param mixed $tmplContent 模板内容
     * @return string
     */
    protected function compiler($tmplContent)
    {
        //模板解析
        $tmplContent = $this->parse($tmplContent);
        // 还原被替换的Literal标签
        $tmplContent = preg_replace_callback('/<!--###literal(\d+)###-->/is', function ($tag) {//literal标签序号
            if (is_array($tag)) $tag = $tag[1];
            // 还原literal标签
            $parseStr = $this->literal[$tag];
            // 销毁literal记录
            unset($this->literal[$tag]);
            return $parseStr;
        }, $tmplContent);
        // 添加安全代码
        $tmplContent = '<?php if (!defined(\'DRIP_VERSION\')) exit(\'No Permission!\');?>' . $tmplContent;
        // 优化生成的php代码
        $tmplContent = str_replace('?><?php', '', $tmplContent);
        //替换模板中的字符串
        $this->replaceTemplateString($tmplContent);

        // 模版编译过滤标签
        return CodeFactory::stripWhiteSpace($tmplContent);
    }

    /**替换模板中的字符串
     * @param string $tmplContent 待查询的字符串
     * @return void
     */
    private function replaceTemplateString(&$tmplContent)
    {
        // 允许用户自定义模板的字符串替换
        if (!empty($this->config['TMPL_PARSE_STRING'])) $this->_replacement = array_merge($this->_replacement, $this->config['TMPL_PARSE_STRING']);
        $tmplContent = str_replace(array_keys($this->_replacement), array_values($this->_replacement), $tmplContent);
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param string $content 要解析的模板内容
     * @return string
     * @throws
     */
    public function parse($content)
    {
        // 内容为空不解析
        if (empty($content)) return '';
        $begin = $this->config['TAGLIB_BEGIN'];
        $end = $this->config['TAGLIB_END'];
        // 检查include语法
        $content = $this->parseInclude($content);
        // 检查PHP语法
        $content = $this->parsePhp($content);
        // 首先替换literal标签内容
        $content = preg_replace_callback('/' . $begin . 'literal' . $end . '(.*?)' . $begin . '\/literal' . $end . '/is', array($this, 'parseLiteral'), $content);

        // 获取需要引入的标签库列表
        // 标签库只需要定义一次，允许引入多个一次
        // 一般放在文件的最前面
        // 格式：<taglib name="html,mytag..." />
        // 当TAGLIB_LOAD配置为true时才会进行检测
        // 预先加载的标签库 无需在每个模板中使用taglib标签加载 但必须使用标签库XML前缀 TAGLIB_PRE_LOAD
        // 内置标签库 无需使用taglib标签导入就可以使用 并且不需使用标签库XML前缀
        $this->parseTagLib($content, true);
        //解析普通模板标签 {$tagName}
        $content = preg_replace_callback('/(' . $this->config['L_DELIM'] . ')([^\d\w\s' . $this->config['L_DELIM'] . $this->config['R_DELIM'] . '].+?)(' . $this->config['R_DELIM'] . ')/is', array($this, 'parseTag'), $content);
        return $content;
    }

    /**
     * 解析资源文件地址
     * 模板文件资源位置格式：
     *      ModuleA/ModuleB@ControllerName/ActionName:themeName
     * @param array|null $context 模板调用上下文环境，包括模块、控制器、方法和模板主题
     * @return string
     */
    public static function parseTemplatePath($context): string
    {
        $context['t'] = isset($context['t']) ? $context['t'] . '/' : '';
        return DRI_PATH_PROJECT . "app/{$context['m']}/view/{$context['t']}{$context['c']}/{$context['a']}";
    }

    // 检查PHP语法
    protected function parsePhp($content)
    {
        if (ini_get('short_open_tag')) {
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
        }
        // PHP语法检查
        return $content;
    }

    // 解析模板中的布局标签
//    protected function parseLayout($content) {}

    /**
     * 解析模板中的include标签
     * @param $content
     * @param bool $extend
     * @return mixed|null|string|string[]
     * @throws DriException
     */
    protected function parseInclude($content, $extend = true)
    {
        // 解析继承
        if ($extend)
            $content = $this->parseExtend($content);
        // 解析布局
//        $content    =   $this->parseLayout($content);
        // 读取模板中的include标签
        $find = preg_match_all('/' . $this->config['TAGLIB_BEGIN'] . 'include\s(.+?)\s*?\/' . $this->config['TAGLIB_END'] . '/is', $content, $matches);
        if ($find) {
            for ($i = 0; $i < $find; $i++) {
                $include = $matches[1][$i];
                $array = $this->parseXmlAttrs($include);
                $file = $array['file'];
                unset($array['file']);
                $content = str_replace($matches[0][$i], $this->parseIncludeItem($file, $array, $extend), $content);
            }
        }
        return $content;
    }

    /**
     * 解析模板中的extend标签
     * @param $content
     * @return mixed|null|string|string[]
     * @throws DriException
     */
    protected function parseExtend($content)
    {
        $begin = $this->config['TAGLIB_BEGIN'];
        $end = $this->config['TAGLIB_END'];
        // 读取模板中的继承标签
        $find = preg_match('/' . $begin . 'extend\s(.+?)\s*?\/' . $end . '/is', $content, $matches);
        if ($find) {
            //替换extend标签
            $content = str_replace($matches[0], '', $content);
            // 记录页面中的block标签
            preg_replace_callback('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/is', array($this, 'parseBlock'), $content);
            // 读取继承模板
            $array = $this->parseXmlAttrs($matches[1]);
            $content = $this->parseTemplateName($array['file']);
            $content = $this->parseInclude($content, false); //对继承模板中的include进行分析
            // 替换block标签
            $content = $this->replaceBlock($content);
        } else {
            $content = preg_replace_callback('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/is', function ($match) {
                return stripslashes($match[2]);
            }, $content);
        }
        return $content;
    }

    /**
     * 分析XML属性
     * @access private
     * @param string $attrs XML属性字符串
     * @return array
     * @throws DriException
     */
    private function parseXmlAttrs($attrs)
    {
        $xml = '<tpl><tag ' . $attrs . ' /></tpl>';
        $xml = simplexml_load_string($xml);
        if (!$xml) throw new DriException("bad xml attributes :$attrs");
        $xml = (array)($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        return $array;
    }

    /**
     * 替换页面中的literal标签
     * @access private
     * @param string $content 模板内容
     * @return string|false
     */
    private function parseLiteral($content)
    {
        if (is_array($content)) $content = $content[1];
        if (trim($content) == '') return '';
        //$content            =   stripslashes($content);
        $i = count($this->literal);
        $parseStr = "<!--###literal{$i}###-->";
        $this->literal[$i] = $content;
        return $parseStr;
    }

    /**
     * 记录当前页面中的block标签
     * @access private
     * @param string $name block名称
     * @param string $content 模板内容
     * @return string
     */
    private function parseBlock($name, $content = '')
    {
        if (is_array($name)) {
            $content = $name[2];
            $name = $name[1];
        }
        $this->block[$name] = $content;
        return '';
    }

    /**
     * 替换继承模板中的block标签
     * @access private
     * @param string $content 模板内容
     * @return string
     */
    private function replaceBlock($content)
    {
        static $parse = 0;
        $begin = $this->config['TAGLIB_BEGIN'];
        $end = $this->config['TAGLIB_END'];
        $reg = '/(' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . ')(.*?)' . $begin . '\/block' . $end . '/is';
        if (is_string($content)) {
            do {
                $content = preg_replace_callback($reg, array($this, 'replaceBlock'), $content);
            } while ($parse && $parse--);
            return $content;
        } elseif (is_array($content)) {
            if (preg_match('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '/is', $content[3])) { //存在嵌套，进一步解析
                $parse = 1;
                $content[3] = preg_replace_callback($reg, array($this, 'replaceBlock'), "{$content[3]}{$begin}/block{$end}");
                return $content[1] . $content[3];
            } else {
                $name = $content[2];
                $content = $content[3];
                $content = isset($this->block[$name]) ? $this->block[$name] : $content;
                return $content;
            }
        }
        return '';
    }

    /**
     * TagLib库解析
     * @access public
     * @param string $content 要解析的模板内容
     * @param boolean $hide 是否隐藏标签库前缀
     * @return void
     */
    public function parseTagLib(&$content, $hide = false)
    {
        $begin = $this->config['TAGLIB_BEGIN'];
        $end = $this->config['TAGLIB_END'];
        $that = $this;
        foreach ($this->tags as $name => $val) {
            $tags = array($name);
            if (isset($val['alias'])) {// 别名设置
                $tags = explode(',', $val['alias']);
                $tags[] = $name;
            }
            $level = isset($val['level']) ? $val['level'] : 1;
            $closeTag = isset($val['close']) ? $val['close'] : true;
            foreach ($tags as $tag) {
                $parseTag = !$hide ? 'Think:' . $tag : $tag;// 实际要解析的标签名称
                if (!method_exists($this, '_' . $tag)) {
                    // 别名可以无需定义解析方法
                    $tag = $name;
                }
                $n1 = empty($val['attr']) ? '(\s*?)' : '\s([^' . $end . ']*)';
//                $this->tempVar = array($tagLib, $tag);
                if (!$closeTag) {
                    $patterns = '/' . $begin . $parseTag . $n1 . '\/(\s*?)' . $end . '/is';
                    $content = preg_replace_callback($patterns, function ($matches) use ($that, $tag) {
                        return $that->parseXmlTag($tag, $matches[1], $matches[2]);
                    }, $content);
                } else {
                    $patterns = '/' . $begin . $parseTag . $n1 . $end . '(.*?)' . $begin . '\/' . $parseTag . '(\s*?)' . $end . '/is';
                    for ($i = 0; $i < $level; $i++) {
                        $content = preg_replace_callback($patterns, function ($matches) use ($that, $tag) {
                            return $that->parseXmlTag($tag, $matches[1], $matches[2]);
                        }, $content);
                    }
                }
            }
        }
    }

    /**
     * 解析标签库的标签
     * 需要调用对应的标签库文件解析类
     * @access public
     * @param string $tag 标签名
     * @param string $attr 标签属性
     * @param string $content 标签内容
     * @return string|false
     * @throws
     */
    public function parseXmlTag($tag, $attr, $content)
    {
        if (ini_get('magic_quotes_sybase'))
            $attr = str_replace('\"', '\'', $attr);
        $parse = '_' . $tag;
        $content = trim($content);
        $tags = $this->parseXmlAttr($attr, $tag);
        return $this->$parse($tags, $content);
    }

    /**
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     * @access public
     * @param string $tagStr 标签内容
     * @return string
     */
    public function parseTag($tagStr)
    {
        if (is_array($tagStr)) $tagStr = $tagStr[2];
        //if (MAGIC_QUOTES_GPC) {
        $tagStr = stripslashes($tagStr);
        //}
        $flag = substr($tagStr, 0, 1);
        $flag2 = substr($tagStr, 1, 1);
        $name = substr($tagStr, 1);
        if ('$' == $flag && '.' != $flag2 && '(' != $flag2) { //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        } elseif ('-' == $flag || '+' == $flag) { // 输出计算
            return '<?php echo ' . $flag . $name . ';?>';
        } elseif (':' == $flag) { // 输出某个函数的结果
            return '<?php echo ' . $name . ';?>';
        } elseif ('~' == $flag) { // 执行某个函数
            return '<?php ' . $name . ';?>';
        } elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr(rtrim($tagStr), -2) == '*/')) {
            //注释标签
            return '';
        }
        // 未识别的标签直接返回
        return $this->config['L_DELIM'] . $tagStr . $this->config['R_DELIM'];
    }

    /**
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $varStr 变量数据
     * @return string
     */
    public function parseVar($varStr)
    {
        $varStr = trim($varStr);
        static $_varParseList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr = '';
        if (!empty($varStr)) {
            $varArray = explode('|', $varStr);
            //取得变量名称
            $var = array_shift($varArray);
            if ('Think.' == substr($var, 0, 6)) {
                // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                $name = $this->parseThinkVar($var);
            } elseif (false !== strpos($var, '.')) {
                //支持 {$var.property}
                $vars = explode('.', $var);
                $var = array_shift($vars);
                switch (strtolower($this->config['VAR_IDENTIFY'])) {
                    case 'array': // 识别为数组
                        $name = '$' . $var;
                        foreach ($vars as $key => $val)
                            $name .= '["' . $val . '"]';
                        break;
                    case 'obj':  // 识别为对象
                        $name = '$' . $var;
                        foreach ($vars as $key => $val)
                            $name .= '->' . $val;
                        break;
                    default:  // 自动判断数组或对象 只支持二维
                        $name = 'is_array($' . $var . ')?$' . $var . '["' . $vars[0] . '"]:$' . $var . '->' . $vars[0];
                }
            } elseif (false !== strpos($var, '[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$" . $var;
                preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
//                $var = $match[1];
            } elseif (false !== strpos($var, ':') && false === strpos($var, '(') && false === strpos($var, '::') && false === strpos($var, '?')) {
                //支持 {$var:property} 方式输出对象的属性
//                $vars = explode(':',$var);
                $var = str_replace(':', '->', $var);
                $name = "$$var";
//                $var  = $vars[0];
            } else {
                $name = "$$var";
            }
            //对变量使用函数
            if (count($varArray) > 0)
                $name = $this->parseVarFunction($name, $varArray);
            $parseStr = '<?php echo (' . $name . '); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name 变量名
     * @param array $varArray 函数列表
     * @return string
     */
    public function parseVarFunction($name, $varArray)
    {
        //对变量使用函数
        $length = count($varArray);
        //取得模板禁止使用函数列表
//        $template_deny_funs = explode(',',$this->config['DENY_FUNC_LIST']);
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            //模板函数过滤
            $fun = trim($args[0]);
            switch ($fun) {
                case 'default':  // 特殊模板函数
                    $name = '(isset(' . $name . ') && (' . $name . ' !== ""))?(' . $name . '):' . $args[1];
                    break;
                default:  // 通用模板函数
//                    if(!in_array($fun,$template_deny_funs)){
                    if (isset($args[1])) {
                        if (strstr($args[1], '###')) {
                            $args[1] = str_replace('###', $name, $args[1]);
                            $name = "$fun($args[1])";
                        } else {
                            $name = "$fun($name,$args[1])";
                        }
                    } else if (!empty($args[0])) {
                        $name = "$fun($name)";
                    }
//                    }
            }
        }
        return $name;
    }

    /**
     * 用于标签属性里面的特殊模板变量解析
     * 格式 以 Think. 打头的变量属于特殊模板变量
     * @access public
     * @param string $varStr 变量字符串
     * @return string
     */
    public function parseThinkVar($varStr)
    {
        if (is_array($varStr)) {//用于正则替换回调函数
            $varStr = $varStr[1];
        }
        $vars = explode('.', $varStr);
        $vars[1] = strtoupper(trim($vars[1]));//identify
        $parseStr = '';
        if (count($vars) >= 3) {
            $vars[2] = trim($vars[2]);
            switch ($vars[1]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . $vars[2] . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $vars[2] . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $vars[2] . '\']';
                    break;
                case 'COOKIE':
                    if (isset($vars[3])) {
                        $parseStr = '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = '$_COOKIE[\'' . $vars[2] . '\']';
                    }
                    break;
                case 'SESSION':
                    if (isset($vars[3])) {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                    } else {
                        $parseStr = '$_SESSION[\'' . $vars[2] . '\']';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . $vars[2] . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);
                    break;
                case 'GLOBALS':
                    $parseStr = '$GLOBALS[\'' . $vars[2] . '\']';
                    break;
                default:
                    $len = count($vars);
                    $parseStr = '$GLOBALS[\'' . $vars[1] . '\']';
                    for ($i = 2; $i < $len; $i++) {
                        $parseStr .= '[\'' . $vars[$i] . '\']';
                    }
            }
        } else if (count($vars) == 2) {
            switch ($vars[1]) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'LITE_VERSION';
                    break;
                default:
                    if (defined($vars[1])) $parseStr = $vars[1];//constant
            }
        }
        return " $parseStr ";
    }

    /**
     * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
     * @access private
     * @param string $tmplPublicName 公共模板文件名
     * @param array $vars 要传递的变量列表
     * @param $extend
     * @return string
     * @throws
     */
    private function parseIncludeItem($tmplPublicName, $vars = array(), $extend)
    {
        // 分析模板文件名并读取内容
        $parseStr = $this->parseTemplateName($tmplPublicName);
        // 替换变量
        foreach ($vars as $key => $val) {
            $parseStr = str_replace('[' . $key . ']', $val, $parseStr);
        }
        // 再次对包含文件进行模板分析
        return $this->parseInclude($parseStr, $extend);
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access private
     * @param string $templateName 模板文件名
     * @return string
     * @throws FileNotFoundException
     */
    private function parseTemplateName($templateName)
    {
        if (substr($templateName, 0, 1) == '$')
            //支持加载变量文件名
            $templateName = $this->get(substr($templateName, 1));
        $array = explode(',', $templateName);
        $parseStr = '';
        foreach ($array as $templateName) {
            if (empty($templateName)) continue;
            if (false === strpos($templateName, $this->config['TEMPLATE_SUFFIX'])) {
                // 解析规则为 模块@主题/控制器/操作 $templateName   =   T($templateName);
                //现在解析规则改为 PATH_BASE.'Application'.'#相对于Application目录的位置#';
                $templateName .= $this->config['TEMPLATE_SUFFIX'];
            }

            if (strpos($templateName, '/') === 0) {
                //Absolute path
                $templateName = DRI_PATH_PROJECT . $templateName;//abs
            } elseif (strpos($templateName, '~/') === 0) {
                //View根目录
                $templateName = dirname(dirname($this->template)) . substr($templateName, 1);
            } else {
                //Relative path
                $templateName = dirname($this->template) . '/' . $templateName;
            }
            $realPath = realpath($templateName);

            if (false === $realPath) {/* 文件不存在时返回false，这样可以少一步判断 */
                throw new FileNotFoundException($templateName);
            } else {
                // 获取模板文件内容
                $parseStr .= file_get_contents($realPath);
            }
        }
        return $parseStr;
    }


    /**
     * 标签库标签列表
     * @var string
     * @access protected
     */
    protected $tagList = array();

    /**
     * 标签库分析数组
     * @var string
     * @access protected
     */
    protected $parse = array();

    /**
     * 标签库是否有效
     * @var string
     * @access protected
     */
    protected $valid = false;

    protected $comparison = array(' nheq ' => ' !== ', ' heq ' => ' === ', ' neq ' => ' != ', ' eq ' => ' == ', ' egt ' => ' >= ', ' gt ' => ' > ', ' elt ' => ' <= ', ' lt ' => ' < ');


    /**
     * TagLib标签属性分析 返回标签属性数组
     * @access public
     * @param string $attr
     * @param string $tag 标签内容
     * @return array|false
     * @throws DriException
     */
    public function parseXmlAttr($attr, $tag)
    {
        //XML解析安全过滤
        $attr = str_replace('&', '___', $attr);
        $xml = '<tpl><tag ' . $attr . ' /></tpl>';
        if (!($xml = simplexml_load_string($xml))) {
            throw new DriException("Bad XML attributes:{$attr}");
        }
        $xml = (array)($xml->tag->attributes());
        if (isset($xml['@attributes'])) {
            $array = array_change_key_case($xml['@attributes']);
            if ($array) {
                $tag = strtolower($tag);
                $item = [];
                if (!isset($this->tags[$tag])) {
                    // 检测是否存在别名定义
                    foreach ($this->tags as $key => $val) {
                        if (isset($val['alias']) && in_array($tag, explode(',', $val['alias']))) {
                            $item = $val;
                            break;
                        }
                    }
                } else {
                    $item = $this->tags[$tag];
                }
                $attrs = explode(',', $item['attr']);
                if (isset($item['must'])) {
                    $must = explode(',', $item['must']);
                } else {
                    $must = array();
                }
                foreach ($attrs as $name) {
                    if (isset($array[$name])) {
                        $array[$name] = str_replace('___', '&', $array[$name]);
                    } elseif (false !== array_search($name, $must)) {
                        throw new DriException($name);
                    }
                }
                return $array;
            }
        }
        return array();
    }

    /**
     * 解析条件表达式
     * @access public
     * @param string $condition 表达式标签内容
     * @return array
     */
    public function parseCondition($condition)
    {
        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is', '$\\1->\\2 ', $condition);
        switch (strtolower($this->config['VAR_IDENTIFY'])) {
            case 'array': // 识别为数组
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"] ', $condition);
                break;
            case 'obj':  // 识别为对象
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1->\\2 ', $condition);
                break;
            default:  // 自动判断数组或对象 只支持二维
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ', $condition);
        }
        if (false !== strpos($condition, '$Think'))
            $condition = preg_replace_callback('/(\$Think.*?)\s/is', array($this, 'parseThinkVar'), $condition);
        return $condition;
    }

    /**
     * 自动识别构建变量
     * @access public
     * @param string $name 变量描述
     * @return string
     */
    public function autoBuildVar($name)
    {
        if ('Think.' == substr($name, 0, 6)) {
            // 特殊变量
            return $this->parseThinkVar($name);
        } elseif (strpos($name, '.')) {
            $vars = explode('.', $name);
            $var = array_shift($vars);
            switch (strtolower($this->config['VAR_IDENTIFY'])) {
                case 'array': // 识别为数组
                    $name = '$' . $var;
                    foreach ($vars as $key => $val) {
                        if (0 === strpos($val, '$')) {
                            $name .= '["{' . $val . '}"]';
                        } else {
                            $name .= '["' . $val . '"]';
                        }
                    }
                    break;
                case 'obj':  // 识别为对象
                    $name = '$' . $var;
                    foreach ($vars as $key => $val)
                        $name .= '->' . $val;
                    break;
                default:  // 自动判断数组或对象 只支持二维
                    $name = 'is_array($' . $var . ')?$' . $var . '["' . $vars[0] . '"]:$' . $var . '->' . $vars[0];
            }
        } elseif (strpos($name, ':')) {
            // 额外的对象方式支持
            $name = '$' . str_replace(':', '->', $name);
        } elseif (!defined($name)) {
            $name = '$' . $name;
        }
        return $name;
    }

    public function addTag($name, $setting, $callback)
    {

    }

    // 获取标签定义
    public function getTags()
    {
        return $this->tags;
    }
    //----------------------------------------------------------------------------------------------------------------------------

    /**
     * volist标签解析 循环输出数据集
     * 格式：
     * <volist name="userList" id="user" empty="" >
     * {user.username}
     * {user.email}
     * </volist>
     * @access public
     * @param array $attr 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _volist($attr, $content)
    {
        $name = $attr['name'];
        $id = $attr['id'];
        $empty = isset($attr['empty']) ? $attr['empty'] : '';
        $key = !empty($attr['key']) ? $attr['key'] : 'i';
        $mod = isset($attr['mod']) ? $attr['mod'] : '2';
        // 允许使用函数设定数据集 <volist name=":fun('arg')" id="vo">{$vo.name}</volist>
        $parseStr = '<?php ';
        if (0 === strpos($name, ':')) {
            $parseStr .= '$_result=' . substr($name, 1) . ';';
            $name = '$_result';
        } else {
            $name = $this->autoBuildVar($name);
        }
        $parseStr .= 'if(is_array(' . $name . ')): $' . $key . ' = 0;';
        if (isset($attr['length']) && '' != $attr['length']) {
            $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $attr['offset'] . ',' . $attr['length'] . ',true);';
        } elseif (isset($attr['offset']) && '' != $attr['offset']) {
            $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $attr['offset'] . ',null,true);';
        } else {
            $parseStr .= ' $__LIST__ = ' . $name . ';';
        }
        $parseStr .=
            'if( count($__LIST__)==0 ) : echo "' . $empty . '" ;
else: 
foreach($__LIST__ as $key=>$' . $id . '): 
$mod = ($' . $key . ' % ' . $mod . ' );
++$' . $key . ';?>';
        $parseStr .= $this->parse($content);
        $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';

        if (!empty($parseStr)) {
            return $parseStr;
        }
        return '';
    }

    /**
     * foreach标签解析 循环输出数据集
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _foreach($tag, $content)
    {
        $name = $tag['name'];
        $item = $tag['item'];
        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(is_array(' . $name . ')): foreach(' . $name . ' as $' . $key . '=>$' . $item . '): ?>';
        $parseStr .= $this->parse($content);
        $parseStr .= '<?php endforeach; endif; ?>';

        if (!empty($parseStr)) {
            return $parseStr;
        }
        return '';
    }

    /**
     * if标签解析
     * 格式：
     * <if condition=" $a eq 1" >
     * <elseif condition="$a eq 2" />
     * <else />
     * </if>
     * 表达式支持 eq neq gt egt lt elt == > >= < <= or and || &&
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _if($tag, $content)
    {
        $condition = $this->parseCondition($tag['condition']);
        $parseStr = '<?php if(' . $condition . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * 格式：见if标签
     * @access public
     * @param array $tag 标签属性
     * @_param string $content  标签内容
     * @return string
     */
    public function _elseif($tag)
    {
        $condition = $this->parseCondition($tag['condition']);
        $parseStr = '<?php elseif(' . $condition . '): ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * @access public
     * @_param array $tag 标签属性
     * @return string
     */
    public function _else()
    {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }

    /**
     * switch标签解析
     * 格式：
     * <switch name="a.name" >
     * <case value="1" break="false">1</case>
     * <case value="2" >2</case>
     * <default />other
     * </switch>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _switch($tag, $content)
    {
        $name = $tag['name'];
        $varArray = explode('|', $name);
        $name = array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if (count($varArray) > 0)
            $name = $this->parseVarFunction($name, $varArray);
        $parseStr = '<?php switch(' . $name . '): ?>' . $content . '<?php endswitch;?>';
        return $parseStr;
    }

    /**
     * case标签解析 需要配合switch才有效
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _case($tag, $content)
    {
        $value = $tag['value'];
        if ('$' == substr($value, 0, 1)) {
            $varArray = explode('|', $value);
            $value = array_shift($varArray);
            $value = $this->autoBuildVar(substr($value, 1));
            if (count($varArray) > 0)
                $value = $this->parseVarFunction($value, $varArray);
            $value = 'case ' . $value . ': ';
        } elseif (strpos($value, '|')) {
            $values = explode('|', $value);
            $value = '';
            foreach ($values as $val) {
                $value .= 'case "' . addslashes($val) . '": ';
            }
        } else {
            $value = 'case "' . $value . '": ';
        }
        $parseStr = '<?php ' . $value . ' ?>' . $content;
        $isBreak = isset($tag['break']) ? $tag['break'] : '';
        if ('' == $isBreak || $isBreak) {
            $parseStr .= '<?php break;?>';
        }
        return $parseStr;
    }

    /**
     * default标签解析 需要配合switch才有效
     * 使用： <default />ddfdf
     * @access public
     * @_param array $tag 标签属性
     * @_param string $content  标签内容
     * @return string
     */
    public function _default()
    {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }

    /**
     * compare标签解析
     * 用于值的比较 支持 eq neq gt lt egt elt heq nheq 默认是eq
     * 格式： <compare name="" type="eq" value="" >content</compare>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @param string $type
     * @return string
     */
    public function _compare($tag, $content, $type = 'eq')
    {
        $name = $tag['name'];
        $value = $tag['value'];
        $type = isset($tag['type']) ? $tag['type'] : $type;
        $type = $this->parseCondition(' ' . $type . ' ');
        $varArray = explode('|', $name);
        $name = array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if (count($varArray) > 0)
            $name = $this->parseVarFunction($name, $varArray);
        if ('$' == substr($value, 0, 1)) {
            $value = $this->autoBuildVar(substr($value, 1));
        } else {
            $value = '"' . $value . '"';
        }
        $parseStr = '<?php if((' . $name . ') ' . $type . ' ' . $value . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    public function _eq($tag, $content)
    {
        return $this->_compare($tag, $content, 'eq');
    }

    public function _equal($tag, $content)
    {
        return $this->_compare($tag, $content, 'eq');
    }

    public function _neq($tag, $content)
    {
        return $this->_compare($tag, $content, 'neq');
    }

    public function _notequal($tag, $content)
    {
        return $this->_compare($tag, $content, 'neq');
    }

    public function _gt($tag, $content)
    {
        return $this->_compare($tag, $content, 'gt');
    }

    public function _lt($tag, $content)
    {
        return $this->_compare($tag, $content, 'lt');
    }

    public function _egt($tag, $content)
    {
        return $this->_compare($tag, $content, 'egt');
    }

    public function _elt($tag, $content)
    {
        return $this->_compare($tag, $content, 'elt');
    }

    public function _heq($tag, $content)
    {
        return $this->_compare($tag, $content, 'heq');
    }

    public function _nheq($tag, $content)
    {
        return $this->_compare($tag, $content, 'nheq');
    }

    /**
     * range标签解析
     * 如果某个变量存在于某个范围 则输出内容 type= in 表示在范围内 否则表示在范围外
     * 格式： <range name="var|function"  value="val" type='in|notin' >content</range>
     * example: <range name="a"  value="1,2,3" type='in' >content</range>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @param string $type 比较类型
     * @return string
     */
    public function _range($tag, $content, $type = 'in')
    {
        $name = $tag['name'];
        $value = $tag['value'];
        $varArray = explode('|', $name);
        $name = array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if (count($varArray) > 0)
            $name = $this->parseVarFunction($name, $varArray);

        $type = isset($tag['type']) ? $tag['type'] : $type;

        if ('$' == substr($value, 0, 1)) {
            $value = $this->autoBuildVar(substr($value, 1));
            $str = 'is_array(' . $value . ')?' . $value . ':explode(\',\',' . $value . ')';
        } else {
            $value = '"' . $value . '"';
            $str = 'explode(\',\',' . $value . ')';
        }
        if ($type == 'between') {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '>= $_RANGE_VAR_[0] && ' . $name . '<= $_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } elseif ($type == 'notbetween') {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '<$_RANGE_VAR_[0] || ' . $name . '>$_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } else {
            $fun = ($type == 'in') ? 'in_array' : '!in_array';
            $parseStr = '<?php if(' . $fun . '((' . $name . '), ' . $str . ')): ?>' . $content . '<?php endif; ?>';
        }
        return $parseStr;
    }

    // range标签的别名 用于in判断
    public function _in($tag, $content)
    {
        return $this->_range($tag, $content, 'in');
    }

    // range标签的别名 用于notin判断
    public function _notin($tag, $content)
    {
        return $this->_range($tag, $content, 'notin');
    }

    public function _between($tag, $content)
    {
        return $this->_range($tag, $content, 'between');
    }

    public function _notbetween($tag, $content)
    {
        return $this->_range($tag, $content, 'notbetween');
    }

    /**
     * present标签解析
     * 如果某个变量已经设置 则输出内容
     * 格式： <present name="" >content</present>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _present($tag, $content)
    {
        $name = $tag['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notpresent标签解析
     * 如果某个变量没有设置，则输出内容
     * 格式： <notpresent name="" >content</notpresent>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _notpresent($tag, $content)
    {
        $name = $tag['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(!isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * empty标签解析
     * 如果某个变量为empty 则输出内容
     * 格式： <empty name="" >content</empty>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _empty($tag, $content)
    {
        $name = $tag['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    public function _notempty($tag, $content)
    {
        $name = $tag['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(!empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * 判断是否已经定义了该常量
     * <defined name='TXT'>已定义</defined>
     * @param <type> $attr
     * @param <type> $content
     * @return string
     */
    public function _defined($tag, $content)
    {
        $name = $tag['name'];
        $parseStr = '<?php if(defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    public function _notdefined($tag, $content)
    {
        $name = $tag['name'];
        $parseStr = '<?php if(!defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }


    /**
     * assign标签解析
     * 在模板中给某个变量赋值 支持变量赋值
     * 格式： <assign name="" value="" />
     * @access public
     * @param array $tag 标签属性
     * @_param string $content  标签内容
     * @return string
     */
    public function _assign($tag)
    {
        $name = $this->autoBuildVar($tag['name']);
        if ('$' == substr($tag['value'], 0, 1)) {
            $value = $this->autoBuildVar(substr($tag['value'], 1));
        } else {
            $value = '\'' . $tag['value'] . '\'';
        }
        $parseStr = '<?php ' . $name . ' = ' . $value . '; ?>';
        return $parseStr;
    }

    /**
     * define标签解析
     * 在模板中定义常量 支持变量赋值
     * 格式： <define name="" value="" />
     * @access public
     * @param array $tag 标签属性
     * @_param string $content  标签内容
     * @return string
     */
    public function _define($tag)
    {
        $name = '\'' . $tag['name'] . '\'';
        if ('$' == substr($tag['value'], 0, 1)) {
            $value = $this->autoBuildVar(substr($tag['value'], 1));
        } else {
            $value = '\'' . $tag['value'] . '\'';
        }
        $parseStr = '<?php define(' . $name . ', ' . $value . '); ?>';
        return $parseStr;
    }

    /**
     * for标签解析
     * 格式： <for start="" end="" comparison="" step="" name="" />
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _for($tag, $content)
    {
        //设置默认值
        $start = 0;
        $end = 0;
        $step = 1;
        $comparison = 'lt';
        $name = 'i';
        $rand = rand(); //添加随机数，防止嵌套变量冲突
        //获取属性
        foreach ($tag as $key => $value) {
            $value = trim($value);
            if (':' == substr($value, 0, 1))
                $value = substr($value, 1);
            elseif ('$' == substr($value, 0, 1))
                $value = $this->autoBuildVar(substr($value, 1));
            switch ($key) {
                case 'start':
                    $start = $value;
                    break;
                case 'end' :
                    $end = $value;
                    break;
                case 'step':
                    $step = $value;
                    break;
                case 'comparison':
                    $comparison = $value;
                    break;
                case 'name':
                    $name = $value;
                    break;
            }
        }

        $parseStr = '<?php $__FOR_START_' . $rand . '__=' . $start . ';$__FOR_END_' . $rand . '__=' . $end . ';';
        $parseStr .= 'for($' . $name . '=$__FOR_START_' . $rand . '__;' . $this->parseCondition('$' . $name . ' ' . $comparison . ' $__FOR_END_' . $rand . '__') . ';$' . $name . '+=' . $step . '){ ?>';
        $parseStr .= $content;
        $parseStr .= '<?php } ?>';
        return $parseStr;
    }

}