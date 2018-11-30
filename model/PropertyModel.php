<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/29
 * Time: 17:41
 */

namespace driphp\model;

/**
 * Class PropertyModel
 * @property string $category
 * @property string $name
 * @property string $title 展示名称
 * @property mixed $value 配置值
 * @property string $description 说明
 * @package driphp\model
 */
class PropertyModel extends Model
{
    public function tableName(): string
    {
        return 'property';
    }


    public function structure(): array
    {
        return [
            'category' => [
                'type' => 'varchar(32)',
                'notnull' => true,
            ],
            'name' => [
                'type' => 'varchar(32)',
                'notnull' => true,
            ],
            'title' => [
                'type' => 'varchar(64)',
                'notnull' => true,
                'comment' => '展示名称',
            ],
            'value' => [
                'type' => 'text',
                'notnull' => true,
                'comment' => '配置值',
            ],
            'description' => [
                'type' => 'varchar(255)',
                'notnull' => true,
                'default' => '',
                'comment' => '说明',
            ],
        ];
    }

    protected function validation(): array
    {
        return [];
    }


}