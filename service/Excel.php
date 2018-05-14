<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/10 0010
 * Time: 20:10
 */

namespace sharin\service;

use stdClass;
use PHPExcel;
use PHPExcel_Reader_Exception;
use PHPExcel_IOFactory;
use PHPExcel_Exception;
use PHPExcel_Style_Alignment;
use PHPExcel_Cell_DataType;
use sharin\core\Service;
use sharin\throws\service\ExcelException;

/**
 * Class Excel
 * @method Excel getInstance(array $config = []) static
 * @package sharin\service
 */
class Excel extends Service
{
    /** @var array 单元格列表 */
    const CELLS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN',
        'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
    ];

    /**
     * @param string $fileName
     * @param PHPExcel $excel
     * @return void
     * @throws ExcelException
     */
    public static function export(string $fileName, PHPExcel $excel)
    {
        ob_end_clean();
        //生成输出下载
        header('Pragma:public');
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=" . $fileName . '.xls'); # 设置附件
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        try {
            $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $objWriter->save('php://output');
        } catch (\PHPExcel_Reader_Exception $e) {
            throw new ExcelException('Read:' . $e->getMessage());
        } catch (\PHPExcel_Writer_Exception $e) {
            throw new ExcelException('Write:' . $e->getMessage());
        }
        exit;
    }

    /**
     * @param string $fileName
     * @param PHPExcel $excel
     * @return void
     * @throws ExcelException
     * @throws PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function save(string $fileName, PHPExcel $excel = null)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ('xls' === $ext) {
            $writerType = 'Excel5';
        } elseif ('xlsx' === $ext) {
            $writerType = 'Excel2007';
        } else {
            throw new ExcelException("错误的文件后缀'$ext'!");
        }
        $objWriter = PHPExcel_IOFactory::createWriter($excel ?? $this->phpExcel, $writerType);
        $objWriter->save($fileName);
    }


    public static function ord(string $char)
    {
        # TODO:支持多字母
        return ord($char) - 64;# A对应1
    }


    /**
     * 获取上传的文件路径,并检查文件的安全性
     * 文件后缀名对应关系:
     *  xls  - PHPExcel_Reader_Excel5
     *  xlsx - PHPExcel_Reader_Excel2007
     * @param string $fieldName 上传表单字段名称
     * @return string 返回文件保存地址
     * @throws ExcelException 导入文件不存在,文件后缀错误,mime类型错误时抛出
     */
    public static function getImportFile(string $fieldName)
    {
        if (empty($_FILES[$fieldName]['type'])) throw new ExcelException("未检测到上传递字段'$fieldName'!");;
        $info = $_FILES[$fieldName];
        $ext = pathinfo($info['name'], PATHINFO_EXTENSION);
        if ($ext !== 'xls' and $ext !== 'xlsx') {
            throw new ExcelException("错误的文件后缀'$ext'!");
        }
        if ($info['type'] === 'application/vnd.ms-excel' or
            $info['type'] === 'application/wps-office.xls'
        ) {
            $info['type'] = 'Excel5';
        } elseif ($info['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            $info['type'] = 'Excel2007';
        } else {
            throw new ExcelException('上传的文件MIME为' . $info['type']);
        }
        $dest = SR_PATH_PUBLIC . 'upload/' . SR_MICROTIME . '-' . $info['name'];
        copy($info['tmp_name'], $dest);
        return $dest;
    }

#################################### READ ##################################################


    /** @var array 读取到的标题数据 */
    private $titles = [];
    /** @var array 读取到的data数据 */
    private $bodies = [];

    /**
     * 获取读取的标题列表
     * @version 1.0
     * @return array
     */
    public function getTitles(): array
    {
        return $this->titles;
    }

    /**
     * 获取读取的主题数据列表
     * @version 1.0
     * @return array
     */
    public function getBodies(): array
    {
        return $this->bodies;
    }

    /**
     * 读取excel文件并返回其中的内容
     * @version 1.0
     * @param string $file 读取的文件路径
     * @param array $map 映射地图 [ 'A'=>'username',...,'AA'=>'email',  ]
     * @param int $dataStartLine 数据区开始行(标题列下一列)
     * @return $this
     * @throws ExcelException
     */
    public function read(string $file, array $map = [], int $dataStartLine = 2)
    {
        $this->titles = $this->bodies = [];
        try {
            $data = [];
            $headlines = null;
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            switch ($extension) {
                case 'xls':
                    $type = 'Excel5';
                    break;
                case 'xlsx':
                    $type = 'Excel2007';
                    break;
                default:
                    throw new ExcelException("file '$file' with bad extension");
            }
            $objReader = PHPExcel_IOFactory::createReader($type);
            $engine = $objReader->load($file/*, 'utf-8'*/);//获取第0张sheet对象
            $sheet = $engine->getSheet(0);//总行数和总列数
            $highestRow = $sheet->getHighestRow();
            $activeSheet = $engine->getActiveSheet();
            # $sheetData = $sheet->toArray(null, true, true, true);# 获取表全部数据,表头所在行默认为数据域开始行的前一行,且无法修改
            # 获取标题列
            if (($titleLine = $dataStartLine - 1) !== 0) {
                # 等于0说明没有标题列
                $row = [];
                foreach ($map as $column => $fieldName) {
                    $cellVal = $activeSheet->getCell($column . '' . $titleLine)->getValue();
                    $row[$fieldName] = $cellVal;
                }
                $this->titles = $row;
            }
            # 获取全部数据
            for ($i = $dataStartLine; $i <= $highestRow; $i++) {
                $row = [];
                foreach ($map as $column => $fieldName) {
                    $cellVal = $activeSheet->getCell($column . '' . $i)->getValue();
                    if (isset($callback)) {
                        $cellVal = $callback($column, $cellVal, $i);
                    }
                    # 判断"等于式"
                    if (preg_match('/=([A-Z]+)\d+/', $cellVal, $match)) {
                        # 遇到"=A4"的情况 当前单元格的值使用另一个单元格的值(通常再同一个行,不同行GG)
                        $object = new stdClass();
                        $object->celNo = Excel::ord($match[1]);
                        $cellVal = $object;
                    }

                    $row[$fieldName] = $cellVal;
                }
                foreach ($row as &$item) {
                    if ($item instanceof stdClass) {
                        $keys = array_keys($row);
                        $item = $row[$keys[$item->celNo - 1]] ?? '';
                    }
                }
                $data[] = $row;
            }
            $this->bodies = $data;
            return $this;
        } catch (PHPExcel_Reader_Exception $e) {
            throw new ExcelException('Read:' . $e->getMessage());
        } catch (PHPExcel_Exception $e) {
            throw new ExcelException($e->getMessage());
        }
    }


    /**
     * Excel计算单元格列的名称
     * @param int $num 相对null的偏移
     * @return string
     */
    public static function chr($num)
    {
        static $cache = array();
        if (!isset($cache[$num])) {
            $num = intval($num);
            $gap = $num - ord('Z');
            if ($gap > 0) {//是否超过一个'Z'的限制
                $pieces = floor($gap / 26); // 几段
                $cache[$num] = self::chr(ord('A') + $pieces) . chr(64 + $gap - $pieces * 26);
            } else {
                $cache[$num] = chr($num);
            }
        }
        return $cache[$num];
    }


    ################################# WRITE ##############################################################

    # 单元格对齐模式
    const ALI_LEFT = 'left';
    const ALI_CENTER = 'center';
    const ALI_RIGHT = 'right';
    # 单元格值的类型
    const TYPE_STR = 's';
    const TYPE_NUM = 'n';
    /** @var string 导出内容第一栏标题,为空时不导出标题 */
    protected $title = '';
    /** @var array 标题栏格式 */
    protected $head = [];
    /** @var array 数据栏列表 */
    protected $body = [];
    /** @var PHPExcel */
    protected $phpExcel = null;

    /**
     * 导出配置列表
     * @var array
     */
    protected $config = [
        'default_cell_width' => 14,
        # 顶部大标题风格
        'top_title_style' => [
            'font' => [
                'bold' => false,
                'color' => ['argb' => '00000000'],
                'size' => 16
            ],
            'alignment' => [
                'horizontal' => self::ALI_CENTER,
                'vertical' => self::ALI_CENTER
            ],
        ],
        'title_style' => [
            'font' => [
                'bold' => false,
                'color' => ['argb' => '00000000'],
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => self::ALI_CENTER,
                'vertical' => self::ALI_CENTER
            ],
        ],
        'body_style' => [
            'font' => [
                'bold' => false,
                'color' => ['argb' => '00000000'],
                'size' => 10
            ],
            'alignment' => [
                'vertical' => self::ALI_CENTER
            ],
        ],
    ];


    protected function initialize()
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        $this->phpExcel = new PHPExcel();
        //由于vendor基于的import使用了内部缓存，可以多次导入一个类,当使用了PHPExcel相关类时必须先调用该函数
    }

    /**
     * 获取PHPExcel对象
     * @param array $titles 标题列表
     * [
     *          'id' => [
     *              'title' => 'ID',
     *              'type' => Excel::TYPE_STR,  # self::TYPE_NUM / self::TYPE_STR 列数据类型
     *              'align' => Excel::ALI_LEFT, #  self::self::ALI_RIGHT / self::ALI_RIGHT / self::ALI_CENTER , 对齐方式
     *              'width' => 14,
     *          ],
     *          'name' => [
     *              'title' => '中文title',
     *              'type' => Excel::TYPE_STR,
     *              'align' => Excel::ALI_RIGHT,
     *              'width' => 25,
     *          ],
     * ]
     * @param array $bodies 数据列表
     * [
     *          [
     *              'id' => '1',
     *              'name' => 'linzh'
     *          ],
     *          [
     *              'id' => '2',
     *              'name' => '中文加char'
     *          ],
     * ]
     * @param string $topTitle 顶部大标题内容文本
     * @return $this
     * @throws ExcelException
     * @throws PHPExcel_Exception
     */
    public function build(array $titles, array $bodies, string $topTitle = '')
    {
        $this->phpExcel = new PHPExcel();
        $cellNum = count($titles);
        $dataNum = count($bodies);
        $keys = array_keys($titles);

        /*-- 设置大标题 --*/
        if (!empty($topTitle)) {
            $columns = Excel::CELLS[$titleColumn = $cellNum - 1]; # 大标题占的列的数目
            $style = $this->config['top_title_style']; # 大标题风格
            try {
                //合并单元格
                $this->phpExcel->getActiveSheet()->mergeCells("A{$titleColumn}:{$columns}{$titleColumn}");
                //设置样式
                $this->phpExcel->getActiveSheet()->getStyle("A{$titleColumn}")->applyFromArray($style);
                //设置单元格值(设置值和样式的区别就是getActiveSheet和setActiveSheetIndex)
                $this->phpExcel->setActiveSheetIndex()->setCellValue("A{$titleColumn}", $topTitle);
            } catch (PHPExcel_Exception $exception) {
                throw new ExcelException('set title failed:' . $exception->getMessage());
            }

            $startLine = 2;
        } else {
            $startLine = 1;
        }
        //记录数据域单元格对齐信息
        $titleAlign = $titleType = [];
        /*-- 设置标题列 --*/
        $activeSheet = $this->phpExcel->getActiveSheet();
        $i = 0;
        foreach ($titles as $val) {
            $cellName = Excel::CELLS[$i] . $startLine;
            $cellValue = $val['title'] ?? $val[0];//用0作角标可以简化配置
            $type = $val['type'] ?? self::TYPE_STR;
            $align = $val['align'] ?? self::ALI_CENTER;
            /** @var \PHPExcel_Worksheet $worksheet */
            $worksheet = $this->phpExcel->setActiveSheetIndex(0);
            $worksheet->setCellValueExplicit($cellName, $cellValue, $type);
            $titleType[$i] = $type;
            $titleAlign[$i] = $align;
            //宽度设置
            $activeSheet->getColumnDimension(Excel::CELLS[$i])->setWidth(
                $val['width'] ?? $this->config['default_cell_width']);
            //标题栏样式设计
            $activeSheet->getStyle($cellName)->applyFromArray($this->config['title_style']);
            $i++;
        }
        $startLine++;
        /*-- 设置数据列 --*/
        for ($i = 0; $i < $dataNum; $i++) {
            $row = $bodies[$i];
            for ($j = 0; $j < $cellNum; $j++) {
//                if (!isset($keys[$j]) or !isset($row[$keys[$j]])) {
//                    continue;
//                }
                $currentCellName = Excel::CELLS[$j] . ($i + $startLine);
                //应用对齐设置
                $alignObj = $activeSheet->getStyle($currentCellName)->getAlignment();
                $alignObj->setHorizontal($titleAlign[$j] ?? PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //值与风格设置
                $valueObj = $this->phpExcel->setActiveSheetIndex(0);
                $valueObj->setCellValueExplicit($currentCellName, $row[$keys[$j] ?? ''] ?? '', $titleType[$j] ?? PHPExcel_Cell_DataType::TYPE_STRING);
                $activeSheet->getStyle($currentCellName)->applyFromArray($this->config['body_style']);
            }
        }
        return $this;
    }


}