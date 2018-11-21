<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/21
 * Time: 11:29
 */

namespace {

    # 将数据表转化成ORM的字段

    use driphp\database\Dao;
    use driphp\Kernel;
    use Symfony\Component\Yaml\Yaml;

    require __DIR__ . '/../bootstrap.php';
    require __DIR__ . '/../vendor/autoload.php';
    $env = __DIR__ . '/../data/env.yaml';
    if (!is_file($env)) {
        copy(__DIR__ . '/../env.sample.yaml', $env);
    }
    $config = Yaml::parse(file_get_contents($env));
    Kernel::getInstance()->init($config)->start();

    $dao = Dao::connect('right');
    $list = $dao->describe('test.test_user');

    $properties = '';
    $map = [
        'char' => 'string', # char varchar
        'test' => 'string', # char varchar
        'datetime' => '\Datetime|string', # char datetime
        'timestamp' => '\Datetime|string', # char datetime
        'date' => 'string', # date
        'int' => 'int', # int tinyint smallint
    ];
    foreach ($list as $item) {
        $field = $item['Field'];
        $type = $item['Type'];
        $t = 'mixed';
        $comment = str_replace("\n", ' ', $item['Comment'] ?? '');
        foreach ($map as $key => $val) {
            if (false !== stripos($type, $key)) {
                $t = $val;
                break;
            }
        }
        $properties .= " * @property {$t} \${$field} {$comment}\n";
    }
    dumpout($list, $properties);
}

/*
array (
  0 =>
  array (
    'Field' => 'id',
    'Type' => 'int(10) unsigned',
    'Collation' => NULL,
    'Null' => 'NO',
    'Key' => 'PRI',
    'Default' => NULL,
    'Extra' => 'auto_increment',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '自增ID',
  ),
  1 =>
  array (
    'Field' => 'username',
    'Type' => 'varchar(255)',
    'Collation' => 'utf8_general_ci',
    'Null' => 'NO',
    'Key' => 'UNI',
    'Default' => NULL,
    'Extra' => '',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '账号名称',
  ),
  2 =>
  array (
    'Field' => 'email',
    'Type' => 'varchar(255)',
    'Collation' => 'utf8_general_ci',
    'Null' => 'NO',
    'Key' => 'UNI',
    'Default' => NULL,
    'Extra' => '',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '',
  ),
  3 =>
  array (
    'Field' => 'password',
    'Type' => 'char(32)',
    'Collation' => 'utf8_general_ci',
    'Null' => 'NO',
    'Key' => '',
    'Default' => 'd93a5def7511da3d0f2d171d9c344e91',
    'Extra' => '',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '账号名称',
  ),
  4 =>
  array (
    'Field' => 'created_at',
    'Type' => 'datetime',
    'Collation' => NULL,
    'Null' => 'NO',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '记录添加时间',
  ),
  5 =>
  array (
    'Field' => 'updated_at',
    'Type' => 'datetime',
    'Collation' => NULL,
    'Null' => 'NO',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '记录修改时间',
  ),
  6 =>
  array (
    'Field' => 'deleted_at',
    'Type' => 'datetime',
    'Collation' => NULL,
    'Null' => 'YES',
    'Key' => '',
    'Default' => NULL,
    'Extra' => '',
    'Privileges' => 'select,insert,update,references',
    'Comment' => '记录软删除时间,为null时候表示已经删除',
  ),
)

==>

 * @property int $id 自增ID
 * @property string $username 账号名称
 * @property string $email
 * @property string $password 账号名称
 * @property string $created_at 记录添加时间
 * @property string $updated_at 记录修改时间
 * @property string $deleted_at 记录软删除时间,为null时候表示已经删除



*/