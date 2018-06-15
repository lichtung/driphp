<?php
/**
 * User: linzhv@qq.com
 * Date: 21/03/2018
 * Time: 12:20
 */
declare(strict_types=1);


namespace driphp\test\database;


use driphp\core\database\Dao;
use driphp\tests\UniTest;

/**
 * Class ModelTest
 *
 * TODO
 * @package driphp\test\database
 */
class ModelTest extends UniTest
{
    /**
     * @return void
     * @throws
     */
    public function testSQLBuilder()
    {
        $master = Dao::getInstance('master');
        $user = UserModel::getInstance($master);

        # test fields
//        $this->compare('SELECT  `username`,`email` FROM test_user;', $user->fields('username,email'));
//        $this->compare('SELECT  * FROM test_user;', $user);
        # test limit
//        $this->compare('SELECT  * FROM test_user LIMIT 1;', $user->limit(1));
//        $this->compare('SELECT  * FROM test_user LIMIT 1,2;', $user->limit(2, 1));
        # table
//        $this->compare('SELECT  * FROM table_not_exist ;', $user->table('table_not_exist'));
//        $this->compare('SELECT  * FROM test_user alias;', $user->alias('alias'));
//        $this->compare('SELECT  * FROM table_not_exist tns;', $user->table('table_not_exist')->alias('tns'));
        # distinct
//        $this->compare('SELECT DISTINCT * FROM test_user;', $user->distinct());
//        $this->compare('SELECT  * FROM test_user;', $user->distinct(false));
        #  group by
//        $this->compare('SELECT  * FROM test_user GROUP BY username;', $user->group('username'));
        # having
//        $this->compare('SELECT  * FROM test_user GROUP BY username HAVING count(id) > 0;', $user->group('username')->having('count(id) > 0'));
        # order by
//        $this->compare('SELECT  * FROM test_user ORDER BY username desc;', $user->order('username desc'));
        # where
//        $user->where([
//            'username' => 'lzh',
//            'email' => 'linzhv@qq.com',
//        ]);
//        $this->compare('SELECT  * FROM test_user WHERE 1 AND `username` = ? AND `email` = ?;', $user);
//        $this->assertArrayEqual(['lzh', 'linzhv@qq.com'], $user->getCombinationBind());
//        # join
//        $this->compare('SELECT  * FROM test_user t
//      JOIN test_tba on tba.k = t.v
//      INNER JOIN test_tbb on tbc.k = t.v
//      LEFT OUTER JOIN test_tbc on tbc.k = t.v
//      WHERE 1 AND `username` = ? AND `email` = ?;
//', $user->alias('t')->join('{{tba}} on tba.k = t.v')->innerJoin('{{tbb}} on tbc.k = t.v')
//            ->leftJoin('{{tbc}} on tbc.k = t.v')->where([
//                'username' => 'lzh',
//                'email' => 'linzhv@qq.com',
//            ]));

        # general
        $user->distinct(true)
            ->fields('username,email')
            ->table('hello_world')->alias('t')
            ->join('{{tba}} on tba.k = t.v')
            ->innerJoin('{{tbb}} on tbc.k = t.v')
            ->leftJoin('{{tbc}} on tbc.k = t.v')
            ->having('count(id) > 0')
            ->where([
                'username' => 'lzh',
                'email' => 'linzhv@qq.com',
            ])->limit(2, 1)
            ->group('username')
            ->order('username desc');
        $this->compare('SELECT DISTINCT `username`,`email` FROM hello_world t
      JOIN test_tba on tba.k = t.v
      INNER JOIN test_tbb on tbc.k = t.v
      LEFT OUTER JOIN test_tbc on tbc.k = t.v
      WHERE 1 AND `username` = ? AND `email` = ?
      GROUP BY username  HAVING count(id) > 0 ORDER BY username desc  LIMIT 1,2   ;
', $user);
    }

    /**
     * @param $sql1
     * @param Model $model
     * @return void
     * @throws
     */
    private function compare($sql1, $model)
    {
        $sql2 = $model->buildSQL();
        $sql1 = str_replace(["\r", "\n", "\t", ' '], '', $sql1);
        $sql2 = str_replace(["\r", "\n", "\t", ' '], '', $sql2);
        if ($sql1 !== $sql2) var_dump("\n" . $sql1 . PHP_EOL . $sql2 . "\n");
        $this->assertTrue($sql1 === $sql2);
        $model->reset();
    }
}