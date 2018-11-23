<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/22
 * Time: 16:54
 */

namespace driphp\model;

use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class RoleModel 角色模型
 * @package driphp\model
 */
class RoleModel extends Model
{
    public function tableName(): string
    {
        return 'role';
    }

    /**
     * @return array
     */
    public function structure(): array
    {
        return [
            'pid' => [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'default' => '0',
                'comment' => '父ID，为0时为顶级',
            ],
            'name' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '角色名称，可以为中文',
            ],
            'description' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '描述信息',
                'default' => '',
            ],
        ];
    }

    protected function validation(): array
    {
        return [
            'name' => [new NotBlank()],
            'pid' => [new Type(['type' => 'integer'])],
        ];
    }


}