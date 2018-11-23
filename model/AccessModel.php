<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/22
 * Time: 16:22
 */

namespace driphp\model;

use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class AccessModel
 * @property string $name 权限名称
 * @property string $value 权限值
 * @property string $type 类型，默认为Path('P')
 * @property string $comment 备注
 * @package driphp\model
 */
class AccessModel extends Model
{
    public function tableName(): string
    {
        return 'access';
    }

    /**
     * @return array
     */
    public function structure(): array
    {
        return [
            'name' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '权限名称',
                'unique' => true,
            ],
            'value' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '权限值',
                'default' => '',
            ],
            'type' => [
                'type' => 'char(1)',
                'notnull' => true,
                'comment' => '类型，默认为Path(\'\'P\'\')',
                'default' => 'P',
            ],
            'comment' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '备注',
                'default' => '',
            ],
        ];
    }

    protected function validation(): array
    {
        return [
            'type' => [
                new Choice(['choices' => ['P'], 'strict' => true]),
            ],
            'value' => [
                new NotBlank(),
            ],
            'name' => [
                new NotBlank(),
            ],
        ];
    }

}