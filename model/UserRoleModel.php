<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/22
 * Time: 19:30
 */

namespace driphp\model;


use Symfony\Component\Validator\Constraints\Type;

/**
 * Class UserRoleModel 用户角色关系表
 * @property int $uid User ID
 * @property int $rid Role ID
 * @package driphp\model
 */
class UserRoleModel extends Model
{
    public function definedFields(): array
    {
        return [];
    }

    public function tableName(): string
    {
        return 'user_role';
    }

    public function primaryKeys(): array
    {
        return ['uid', 'rid'];
    }

    public function primaryKey(): string
    {
        return '';
    }

    public function structure(): array
    {
        return [
            'uid' => [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'comment' => 'User ID',
                'foreign' => [
                    'table' => 'user',
                    'field' => 'id',
                ],
            ],
            'rid' => [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'comment' => 'Role ID',
                'foreign' => [
                    'table' => 'role',
                    'field' => 'id',
                ],
            ],
        ];
    }

    protected function validation(): array
    {
        return [
            'uid' => [new Type(['type' => 'integer'])],
            'rid' => [new Type(['type' => 'integer'])],
        ];
    }

}