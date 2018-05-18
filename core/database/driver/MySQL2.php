<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 15:52
 */

namespace sharin\core\database\driver;

use PDO;
use sharin\throws\core\database\QueryException;
use sharin\throws\io\FileWriteException;

/**
 * Class MySQL2
 * @deprecated
 * @package sharin\core\database\driver
 */
class MySQL2 extends PDO
{

    /**
     * 备份表
     * @param string $table 表名
     * @param bool $withData 同时备份数据
     * @return bool
     * @throws
     */
    function backup(string $table, bool $withData = true): bool
    {

        $structure = "\n";
        $datetime = date('Y-m-d H:i:s');
        //备份表结构
        $result = $this->query("SHOW CREATE TABLE `{$table}`")->fetchAll();

        $create_sql = isset($result[0]['Create Table']) ? $result[0]['Create Table'] : '';
        if (!$create_sql) {
            throw new QueryException((string)$this->errorInfo());
        }
        $create_sql = preg_replace('/AUTO_INCREMENT=\d+/', '', $create_sql, 1);

        $structure .= "-- -----------------------------\n";
        $structure .= "-- Table structure for `{$table}` at {$datetime} \n";
        $structure .= "-- -----------------------------\nSET FOREIGN_KEY_CHECKS=0;\n";
        $structure .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $structure .= trim($create_sql) . ";\n\n";

        if (!$this->_write($table, $structure)) {
            return false;
        }

        if ($withData) {
            //数据总数
            $result = $this->query("SELECT COUNT(*) AS c FROM `{$table}`")->fetch();
            //备份表数据
            if (isset($result['c']) ? $result['c'] : 0) {
                //写入数据注释
                $data_sql = "-- -----------------------------\n";
                $data_sql .= "-- Records of table `{$table}`\n";
                $data_sql .= "-- -----------------------------\n";

                //备份数据记录
                $stmt = $this->query("SELECT * FROM `{$table}`;");
                $c = 0;
                while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
                    $fields = '';
                    foreach ($row as &$item) {
                        if ($item === null) {
                            $fields .= 'NULL,';
                        } else {
                            $fields .= '\'' . str_replace(["\r", "\n", '\''], ['\r', '\n', '"'], addslashes($item)) . '\',';
                        }
                    }
                    $fields = rtrim($fields, ',');
                    $data_sql .= "INSERT INTO `{$table}` VALUES ( {$fields} ); \n";

                    if ($c++ > 1000) {
                        if (!$this->_write($table, $data_sql, true)) {
                            return false;
                        }
                        $data_sql = '';
                        $c = 0;
                    }
                }
                $stmt->closeCursor();
                unset($stmt);
                $this->_write($table, $data_sql, true);
            }
        }
        return true;
    }

    /**
     * @param string $table
     * @param string $sql
     * @param bool $append
     * @return bool
     * @throws FileWriteException
     */
    protected function _write(string $table, string $sql, bool $append = false): bool
    {
        $file = SR_PATH_DATA . 'backup/' . $table . '/' . time() . '.sql';
        is_dir($dir = dirname($file)) or mkdir($dir, 0755, true);
        if (false === ($res = file_put_contents($file, $sql, $append ? FILE_APPEND : 0))) {
            throw new FileWriteException($file);
        }
        return $res > 0;
    }

}