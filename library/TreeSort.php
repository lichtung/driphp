<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 17:35
 */
declare(strict_types=1);


namespace driphp\library;


final class TreeSort
{
    /**
     * 原始字符串列表
     * @var array
     */
    protected $rawlist = null;

    protected $nestedList = [];

    protected $leveledList = [];

    public function __construct(array $list)
    {
        $this->rawlist = $list;
        $list = [];
        foreach ($this->rawlist as $key => $item) {
            if ($item['pid'] == 0) {
                $item['children'] = [];
                $id = $item['id'];
                $list[$id] = $item;
                unset($this->rawlist[$key]);
                $this->_build($list[$id]);
            }
        }
        $this->nestedList = $list;
    }

    /**
     * 一纬转多维
     * @param array $item
     * @return void
     */
    private function _build(array &$item)
    {
        foreach ($this->rawlist as $k => $v) {
            $childId = $v['id'];
            if ($childId == $item['id']) {
                # 是它的子元素
                $item['children'][$childId] = $v;
                unset($this->rawlist[$k]);
                $this->_build($item['children'][$childId]);
            }
        }
    }

    /**
     * 多维转一维
     * @param array $list
     * @param int $level
     * @return void
     */
    private function _debuild(array &$list, $level = 0)
    {
        foreach ($list as $item) {
            $item['level'] = $level;
            $this->leveledList[] = $item;
            if (!empty($item['children'])) {
                $this->_debuild($item['children'], $level + 1);
            }
        }
        foreach ($this->leveledList as &$item) {
            unset($item['children']);
        }
    }

    /**
     * @param bool $nested 是否转为嵌套的列表
     * @return array
     */
    public function tolist($nested = true): array
    {
        if ($nested) {
            return $this->nestedList;
        } else {
            $this->_debuild($this->nestedList, 0);
            return $this->leveledList;
        }
    }

}