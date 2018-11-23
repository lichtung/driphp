<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/22
 * Time: 19:39
 */

namespace driphp\model;


use Symfony\Component\Validator\Constraints\Type;

/**
 * Class RoleAccessModel
 * @property int $rid Role ID
 * @property int $aid Access ID
 * @package driphp\model
 */
class RoleAccessModel extends Model
{

    public function definedFields(): array
    {
        return [];
    }

    public function tableName(): string
    {
        return 'role_access';
    }

    public function primaryKeys(): array
    {
        return ['rid', 'aid'];
    }

    public function primaryKey(): string
    {
        return '';
    }

    public function structure(): array
    {
        return [
            'rid' => [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'comment' => 'Role ID',
                'foreign' => [
                    'table' => 'role',
                    'field' => 'id',
                ],
            ],
            'aid' => [
                'type' => 'int(10) unsigned',
                'notnull' => true,
                'comment' => 'Access ID',
                'foreign' => [
                    'table' => 'access',
                    'field' => 'id',
                ],
            ],
        ];
    }

    protected function validation(): array
    {
        return [
            'rid' => [new Type(['type' => 'integer'])],
            'aid' => [new Type(['type' => 'integer'])],
        ];
    }

}