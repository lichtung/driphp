<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 15:55
 */

namespace driphp\core\response;


use driphp\core\Response;

class File extends Response
{

    /**
     * 下载文件
     * 注:
     *  /   -   %2f
     *
     * 用法示例:
     *
     * public function download(string $filename, int $justdownload = 1) {
     *      FileIO::download($filename, $justdownload > 0);
     * }
     * 使用:
     * /download?justdownload=0&filename=upload%2fa.jpg将在页面上显示a.jpg图片
     * /download?justdownload=1&filename=upload%2fa.jpg将直接下载文件并保存为a.jpg
     *
     * @param string $file 文件名称,相对于data目录
     * @param bool $straightly 为true时直接开启下载,否则将在页面上显示
     * @return void
     */
    function download(string $file, bool $straightly = true)
    {
        $_allow_mime_map = [
            'gif' => 'image/gif',
            'png' => 'image/png',
            'jpeg' => 'image/jpg',
            'jpg' => 'image/jpg',
            'pdf' => 'application/pdf',
//        'exe'=>'application/octet-stream',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/x-wav',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
        ];
//    Developer::closeTrace();
        # 安全处理
        if (strpos($file, '%') !== false) $file = urldecode($file);
        $file = str_replace(['..', ' ', '\\'], '', $file); # 禁止空格和访问上级目录

        //First, see if the file exists
        $file = SR_PATH_DATA . $file;
        if (!is_file($file)) {
            $this->setStatus(404);
            die;
        }
        if (!is_readable($file)) {
            $this->setStatus(401);
            die;
        }

        //Gather relevent info about file
        $pathinfo = pathinfo($file);
        $filename = $pathinfo['basename'];
        $file_extension = $pathinfo['extension'];

        //This will set the Content-Type to the appropriate setting for the file
        if (isset($_allow_mime_map[$file_extension])) {
            //Begin writing headers
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: public');
            header('Content-Description: File Transfer');

            //Use the switch-generated Content-Type
            header('Content-Type: ' . $_allow_mime_map[$file_extension]);

            //Force the download
            # 代码里面使用Content-Disposition来确保浏览器弹出下载对话框的时候,否则再页面上显示
            $straightly and header("Content-Disposition: attachment; filename={$filename};");
            header('Content-Transfer-Encoding: binary');
            header("Content-Length: " . filesize($file));
            readfile($file);
        } else {
            $this->setStatus(401);
        }
    }
}