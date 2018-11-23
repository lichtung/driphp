<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/22
 * Time: 16:41
 */

namespace driphp\model;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ReflectModel
 *
 * @property string $module 模块名称
 * @property string $controller 控制器名称
 * @property string $action 方法名称
 * @property string $path 路径：module/controller/action
 * @property string $description 描述信息
 *
 * @package driphp\model
 */
class ReflectModel extends Model
{
    public function tableName(): string
    {
        return 'reflect';
    }

    public function structure(): array
    {
        return [
            'module' => [
                'type' => 'varchar(96)',
                'notnull' => true,
                'default' => '', # 模块可以为空
                'comment' => '模块名称',
            ],
            'controller' => [
                'type' => 'varchar(32)',
                'notnull' => true,
                'comment' => '控制器名称',
            ],
            'action' => [
                'type' => 'varchar(128)',
                'notnull' => true,
                'comment' => '方法名称',
            ],
            'path' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '路径：module/controller/action',
                'unique' => true,
            ],
            'description' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'comment' => '描述信息',
            ],
        ];
    }

    protected function validation(): array
    {
        return [
            'module' => [],
            'controller' => [new NotBlank()],
            'action' => [new NotBlank()],
            'path' => [new NotBlank()],
            'description' => [],
        ];
    }


}