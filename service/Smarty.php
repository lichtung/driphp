<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 15:34
 */
declare(strict_types=1);


namespace sharin\service;

/**
 * Class Smarty
 * @deprecated
 * @package sharin\service
 */
class Smarty
{

    protected $_context = null;

    protected $convention = null;

    /**
     * 模板变量
     * @var array
     */
    protected $_tVars = [];

    /**
     * @var \SmartyBC
     */
    private $smarty = null;

    public function __construct(array $config = null)
    {
        if (null === $this->convention) {
            $this->convention = [
                'TEMPLATE_CACHE_DIR' => DRIP_PATH_RUNTIME . 'view/',

                'SMARTY_CONF' => [
                    //模板变量分割符号
                    'left_delimiter' => '{',
                    'right_delimiter' => '}',
                    //缓存开启和缓存时间
                    'caching' => true,
                    'cache_lifetime' => 15,
                ],
            ];
        }
        if (!isset($this->smarty)) {
            $this->smarty = new \Smarty();
            $config and $this->convention = array_merge($this->convention, $config);
            if (isset($this->convention['SMARTY_CONF'])) {
                foreach ($this->convention['SMARTY_CONF'] as $name => $value) {
                    $this->smarty->{$name} = $value;
                }
            }
        }
    }

    /**
     * 保存控制器分配的变量
     * @param string $tpl_var
     * @param null $value
     * @return $this
     */
    public function assign($tpl_var, $value = null)
    {
        if (is_array($tpl_var)) {
            $this->_tVars = array_merge($this->_tVars, $tpl_var);
        } else {
            $this->_tVars[$tpl_var] = $value;
        }
        return $this;
    }

    /**
     * 设置上下文环境
     * @param array $context 上下文环境，包括模块、控制器、方法和模板信息可供设置使用
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->_context = $context;
        return $this;
    }

    /**
     * 显示模板
     * @param string $context 模板文件位置
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return void
     * @throws FileNotFoundException
     */
    public function display($context = null, $cache_id = null, $compile_id = null, $parent = null)
    {

        //拟上下文转化成数组
        $context = &$this->_context;
        //判断模板文件是否存在（改为由模板引擎判断）
        if (!is_file($context)) throw new FileNotFoundException($context);

        //编译缓存目录
        $cachedir = $this->convention['TEMPLATE_CACHE_DIR'] . "{$context['m']}/{$context['c']}/";

        //分配变量
        $this->smarty->assign($this->_tVars);
        //设置模板缓存目录
        $this->smarty->setCompileDir("{$cachedir}compile/");
        $this->smarty->setCacheDir("{$cachedir}cache/");

        //显示模板文件
        $this->smarty->display($context, $cache_id, $compile_id, $parent);
    }
}