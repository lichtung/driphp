<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 17:01
 */

namespace driphp\library\extra;


use driphp\Component;

class TreeSort extends Component
{
    protected $config = [
        'pidKey' => 'pid',
        'idKey' => 'id',
        'childrenKey' => 'children',
    ];
    /** @var string */
    private $pidKey;
    /** @var string */
    private $idKey;
    /** @var string */
    private $childrenKey;

    protected function initialize()
    {
        $this->pidKey = $this->config['pidKey'];
        $this->idKey = $this->config['idKey'];
        $this->childrenKey = $this->config['childrenKey'];
    }

    protected $list = null;

    protected $nested = [];
    protected $leveled = [];

    public function bind(array $list)
    {
        $this->list = $list;
        $list = [];
        foreach ($this->list as $key => $item) {
            if (empty($item[$this->pidKey])) {
                $item[$this->childrenKey] = [];
                $id = $item[$this->idKey];
                $list[$id] = $item;
                unset($this->list[$key]);
                $this->_build($list[$id], 0);
            }
        }
        $this->nested = $list;
        return $this;
    }

    private function _build(array &$item, int $level = 0)
    {
        $id = $item[$this->idKey];
        if ($level < 10) {
            foreach ($this->list as $k => $v) {
                if ($v[$this->pidKey] == $id) {
                    $vid = $v[$this->idKey];
                    $item[$this->childrenKey][$vid] = $v;
                    unset($this->list[$k]);
                    $this->_build($item[$this->childrenKey][$vid], $level + 1);
                }
            }
        }
    }

    private function _debuild(&$list, $level = 0)
    {
        foreach ($list as $item) {
            $item['level'] = $level;
            $this->leveled[] = $item;
            if (!empty($item[$this->childrenKey])) {
                $this->_debuild($item[$this->childrenKey], $level + 1);
            }
        }
        foreach ($this->leveled as &$item) {
            unset($item[$this->childrenKey]);
        }
    }

    private function removeKeys(&$list)
    {
        $list = array_values($list);
        foreach ($list as &$item) {
            unset($item[$this->pidKey]);
            if (!empty($item[$this->childrenKey])) {
                $this->removeKeys($item[$this->childrenKey]);
            }
        }
        return $list;
    }

    /**
     * @param bool $nested 是否转为嵌套的列表
     * @param bool $keepKeys
     * @return array
     */
    public function toArray($nested = true, bool $keepKeys = true)
    {
        if ($nested) {
            if ($keepKeys) {
                return $this->nested;
            } else {
                $data = $this->nested;
                return $this->removeKeys($data);
            }
        } else {
            $this->_debuild($this->nested, 0);
            return $this->leveled;
        }
    }
}