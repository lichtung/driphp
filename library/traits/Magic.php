<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/26 0026
 * Time: 18:43
 */
declare(strict_types=1);


namespace sharin\library\traits;

/**
 * Trait Magic 魔术访问
 * @package sharin\library\traits
 */
trait Magic
{

    private $_properties = [];

    public function __set(string $var, $val)
    {
        $this->_properties[$var] = $val;
    }

    public function __get(string $var)
    {
        return $this->_properties[$var] ?? null;
    }

    public function __toString(): string
    {
        return json_encode($this->_properties);
    }
}