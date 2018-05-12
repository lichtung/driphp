<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/28 0028
 * Time: 17:40
 */
declare(strict_types=1);

namespace sharin\service\workerman\socketio;

/**
 * Class Passport
 *
 *
 * @property string $id
 * @property string $name
 *
 *
 * @package lite\service\socketio
 */
class Passport
{
    protected $data = [];

    public function __construct(string $id, string $name = '')
    {
        $this->data['id'] = $id;
        $this->data['name'] = $name;
    }

    public function __get($name)
    {
        return $this->data[$name]??null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}