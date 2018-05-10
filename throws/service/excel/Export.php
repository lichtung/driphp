<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/10 0010
 * Time: 20:20
 */

namespace sharin\service\excel;

use PHPExcel;
use PHPExcel_Exception;
use PHPExcel_Style_Alignment;
use PHPExcel_Cell_DataType;
use sharin\service\Excel;
use sharin\throws\service\ExcelException;

class Export
{
    # 单元格对齐模式
    const ALI_LEFT = 0;
    const ALI_CENTER = 1;
    const ALI_RIGHT = 2;
    # 单元格值的类型
    const TYPE_STR = 1;
    const TYPE_NUM = 2;
    //样式预定义
    protected $titleStyle = null;
    protected $littleTitleStyle = null;
    protected $defaultBodyStyle = null;
    /**
     * 导出配置列表
     * @var array
     */
    protected $config = [
        'defalut_cell_width' => 14,
    ];
    /**
     * 导出内容第一栏标题,为空时不导出标题
     * @var string
     */
    protected $title = '';
    /**
     * 标题栏格式
     * @var array
     */
    protected $head = [];
    /**
     * 数据栏列表
     * @var array
     */
    protected $body = [];
    /**
     * @var PHPExcel
     */
    protected $engine = null;

    /**
     * Export constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config and $this->config = array_merge($this->config, $config);
        $this->engine = Excel::getPhpExcel();
        //由于vender基于的import使用了内部缓存，可以多次导入一个类,当使用了PHPExcel相关类时必须先调用该函数
        $this->titleStyle = [
            'font' => [
                'bold' => false,
                'color' => ['argb' => '00000000'],
                'size' => 16
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
        ];
        $this->littleTitleStyle = [
            'font' => [
                'bold' => false,
                'color' => ['argb' => '00000000'],
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
        ];
        $this->defaultBodyStyle = [
            'font' => [
                'bold' => false,
                'color' => array('argb' => '00000000'),
                'size' => 10
            ],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
        ];
    }

    /**
     * 设置标题
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置标题栏格式
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
     * @param array $head
     * @return $this
     */
    public function setHead($head)
    {
        $this->head = $head;
        return $this;
    }

    /**
     * 设置导出数据栏列表
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
     * @param array $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * 设置到处的excel的大标题
     * @param string $title 大标题文本
     * @param int $columns 大标题占的列的数目
     * @param array $style 大标题风格
     * @throws ExcelException
     */
    private function setBigTitle($title, $columns, $style = null)
    {
        try {
            //合并单元格
            $this->engine->getActiveSheet()->mergeCells('A1:' . $columns . '1');
            //设置样式
            $this->engine->getActiveSheet()->getStyle('A1')->applyFromArray(isset($style) ? $style : $this->titleStyle);
            //设置单元格值(设置值和样式的区别就是getActiveSheet和setActiveSheetIndex)
            $this->engine->setActiveSheetIndex()->setCellValue('A1', $title);
        } catch (PHPExcel_Exception $exception) {
            throw new ExcelException('set title failed:' . $exception->getMessage());
        }
    }

    /**
     * 获取PHPExcel对象
     * @return PHPExcel
     * @throws ExcelException
     * @throws PHPExcel_Exception
     */
    public function fetch()
    {
        $cellTitle = $this->head;
        $cellValue = $this->body;
        $cellNum = count($cellTitle);
        $dataNum = count($cellValue);
        $keys = array_keys($cellTitle);
        /*-- 设置大标题 --*/
        if (!empty($data['title'])) {
            $this->setBigTitle($data['title'], Excel::$cellName[$cellNum - 1],
                isset($data['titledstyle']) ? $data['titledstyle'] : null);
            $startLine = 2;
        } else {
            $startLine = 1;
        }
        //记录数据域单元格对齐信息
        $titlealign = array();
        $titletype = array();
        /*-- 设置标题列 --*/
        $activeSheet = $this->engine->getActiveSheet();
        $i = 0;
        foreach ($cellTitle as $val) {
            $cellname = Excel::$cellName[$i] . $startLine;
            $cellval = isset($val['title']) ? $val['title'] : $val[0];//用0作角标可以简化配置
            //居中设置
            switch ($val['type']) {
                case self::TYPE_NUM:
                    $this->engine->setActiveSheetIndex(0)->setCellValueExplicit($cellname, $cellval,
                        PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    break;
                case self::TYPE_STR:
                case null://null的情况下设为字符模式
                default:
                    $this->engine->setActiveSheetIndex(0)->setCellValueExplicit($cellname, $cellval,
                        PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $titletype[$i] = $val['type'];
            $titlealign[$i] = $val['align'];
            //宽度设置
            $activeSheet->getColumnDimension(Excel::$cellName[$i])->setWidth(
                isset($val['width']) ? $val['width'] : $this->config['defalut_cell_width']);
            //标题栏样式设计
            $activeSheet->getStyle($cellname)->applyFromArray(
                isset($data['headstyle']) ? $data['headstyle'] : $this->littleTitleStyle);
            $i++;
        }
        $startLine++;
        /*-- 设置数据列 --*/
        for ($i = 0; $i < $dataNum; $i++) {
            $row = $cellValue[$i];
            for ($j = 0; $j < $cellNum; $j++) {
                $curcellname = Excel::$cellName[$j] . ($i + $startLine);
                //应用对齐设置
                $alignObj = $activeSheet->getStyle($curcellname)->getAlignment();
                switch ($titlealign[$j]) {
                    case self::ALI_RIGHT:
                        $alignObj->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                        break;
                    case self::ALI_LEFT:
                        $alignObj->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        break;
                    case self::ALI_CENTER://默认居中
                    default:
                        $alignObj->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                //值与风格设置
                $valueObj = $this->engine->setActiveSheetIndex(0);
                switch ($titletype[$j]) {
                    case self::TYPE_NUM:
                        $valueObj->setCellValueExplicit($curcellname, $row[$keys[$j]], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        break;
                    case self::TYPE_STR:
                    case null://null的情况下设为字符模式
                    default:
                        $valueObj->setCellValueExplicit($curcellname, $row[$keys[$j]], PHPExcel_Cell_DataType::TYPE_STRING);
                }
                $activeSheet->getStyle($curcellname)->applyFromArray($data['bodystyle'] ?? $this->defaultBodyStyle);
            }
        }
        return $this->engine;
    }


}