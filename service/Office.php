<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 15:38
 */
declare(strict_types=1);


namespace sharin\service;

use sharin\throwable\service\OfficeException;
use PHPExcel;
use PHPExcel_IOFactory;

/**
 * Class Office
 * TODO:
 * @package sharin\service
 */
class Office
{

    public static $cellName = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN',
        'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
    ];

    /**
     * @param string $fileName
     * @param PHPExcel $excel
     * @return void
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function exportExcel(string $fileName, PHPExcel $excel)
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
        $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }


    /**
     * Excel计算单元格列的名称
     * @param int $num 相对null的偏移
     * @return string
     */
    public static function chr(int $num)
    {
        static $cache = array();
        if (!isset($cache[$num])) {
            $num = intval($num);
            $gap = $num - ord('Z');
            if ($gap > 0) {//是否超过一个'Z'的限制
                $pieceNum = floor($gap / 26); // 几段
                $cache[$num] = self::chr(ord('A') + $pieceNum) . chr(64 + $gap - $pieceNum * 26);
            } else {
                $cache[$num] = chr($num);
            }
        }
        return $cache[$num];
    }

    /**
     * 获取PHPExcel对象
     * @return PHPExcel
     */
    public static function getPhpExcel(): PHPExcel
    {
        static $instance = null;
        if (!$instance) {
            $instance = new PHPExcel();
        }
        return $instance;
    }

    /**
     * 获取上传的文件路径,并检查文件的安全性
     * 文件后缀名对应关系:
     *  xls  - PHPExcel_Reader_Excel5
     *  xlsx - PHPExcel_Reader_Excel2007
     * @param $fieldName
     * @return string
     * @throws OfficeException 导入文件不存在,文件后缀错误,mime类型错误时抛出
     */
    public static function getImportFile($fieldName)
    {
        if (empty($_FILES[$fieldName]['type'])) throw new OfficeException("未检测到上传递字段'$fieldName'!");;
        $info = $_FILES[$fieldName];
        $ext = pathinfo($info['name'], PATHINFO_EXTENSION);
        if ($ext !== 'xls' and $ext !== 'xlsx') {
            throw new OfficeException("错误的文件后缀'$ext'!");
        }
        if ($info['type'] === 'application/vnd.ms-excel' or
            $info['type'] === 'application/wps-office.xls'
        ) {
            $info['type'] = 'Excel5';
        } elseif ($info['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            $info['type'] = 'Excel2007';
        } else {
            throw new OfficeException('上传的文件MIME为' . $info['type']);
        }
        $dest = DRIP_PATH_DATA . 'upload/' . DRIP_NOW_MICRO . '-' . $info['name'];
        copy($info['tmp_name'], $dest);
        return $dest;
    }
}