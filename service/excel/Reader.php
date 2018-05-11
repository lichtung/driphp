<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/10 0010
 * Time: 20:21
 */

namespace sharin\service\excel;

use stdClass;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use sharin\service\Excel;
use sharin\throws\service\ExcelException;

class Reader
{
    /**
     * 导入的文件路径
     * @var string
     */
    protected $filename = '';
    /**
     *  数据域映射关系对象 兼 表头域验证
     * @var array
     */
    protected $map = [];

    public function __construct($filename)
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $this->filename = $filename;
    }

    /**
     * 设置数据域映射关系
     * 如:
     *```php
     * [
     *  'id' => [
     *      'column' => 'A',
     *      'text' => 'ID',
     *  ],
     *  'phone' => [
     *      'column' => 'B',
     *      'text' => '帐号',
     *  ],
     *  'role' => [
     *      'column' => 'C',
     *      'text' => '角色',
     *  ],
     * ]
     * ```
     * @param array $map
     * @return $this
     */
    public function setMap(array $map)
    {
        $this->map = $map;
        return $this;
    }

    /**
     * @param callable|null $callback 值回调,参数一是单元格名称(A,B,C...),参数二是单元格的值,参数三是列序号(excel中实际的列)
     * @param int $dataStartLine 数据开始的行，默认为2
     * @return array
     * @throws ExcelException
     */
    public function fetch(callable $callback = null, $dataStartLine = 2)
    {
        try {
            $data = [];
            $headlines = null;
            switch (pathinfo($this->filename, PATHINFO_EXTENSION)) {
                case 'xls':
                    $type = 'Excel5';
                    break;
                case 'xlsx':
                    $type = 'Excel2007';
                    break;
                default:
                    throw new ExcelException("文件'{$this->filename}'无法导入");
            }
            $objReader = PHPExcel_IOFactory::createReader($type);
            $engine = $objReader->load($this->filename/*, 'utf-8'*/);//获取第0张sheet对象
            $sheet = $engine->getSheet(0);//总行数和总列数
            $highestRow = $sheet->getHighestRow();
            $activeSheet = $engine->getActiveSheet();
            $sheetData = $sheet->toArray(null, true, true, true);//获取表头数组 表头所在行默认为数据域开始行的前一行,且无法修改
            $headLineNo = $dataStartLine - 1;
            if ($headLineNo < 0) {
                throw new ExcelException('The cause due to lacking of headline! ');
            }
            /**
             * 标题列:
             *  array (
             *      'A' => 'ID',
             *      'B' => '姓名',
             *      'C' => '手机',
             * )
             */
            $headlines = $sheetData[$headLineNo];
            for ($i = $dataStartLine; $i <= $highestRow; $i++) {
                //仅第一次验证headline
//                if ($i == $dataStartLine) {
//                    foreach ($this->map as $key => $val) {
//                        $column = isset($val['column']) ? $val['column'] : $val[0];
//                        $columnTitle = isset($val['text']) ? $val['text'] : $val[1];
//                        //不想要验证这一行，可能是无关紧要的数据
//                        if (isset($columnTitle)) {
//                            if (trim($headlines[$column]) != trim($columnTitle)) {
//                                throw new ExcelException('The headline ' . $column . ' is not ' . $columnTitle . '! ');
//                            }
//                        }
//                    }
//                }
                //获取数据域
                $row = [];
                foreach ($this->map as $key => $val) {
                    $column = is_array($val) ? $val[0] : $val;
                    $cellVal = $activeSheet->getCell($column . '' . $i)->getValue();
                    if (isset($callback)) {
                        $cellVal = $callback($column, $cellVal, $i);
                    }
                    if (preg_match('/=([A-Z]+)\d+/', $cellVal, $match)) {
                        # 遇到"=A4"的情况 当前单元格的值使用另一个单元格的值(通常再同一个行,不同行GG)
                        $object = new stdClass();
                        $object->celNo = Excel::ord($match[1]);
                        $cellVal = $object;
                    }
                    $row[$key] = $cellVal;
                }
                foreach ($row as &$item) {
                    if ($item instanceof stdClass) {
                        $keys = array_keys($row);
                        $item = $row[$keys[$item->celNo - 1]] ?? '';
                    }
                }
                $data[] = $row;
            }
            return $data;
        } catch (PHPExcel_Reader_Exception $e) {
            throw new ExcelException('Read:' . $e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            throw new ExcelException($e->getMessage());
        }
    }

}