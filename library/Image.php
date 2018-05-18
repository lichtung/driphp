<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 15:54
 */

namespace sharin\library;


class Image
{

    /**
     * ResizeImage(__DIR__ . '/1283b6562689c9012b981f2cbab9880e.jpeg', 500, 400, __DIR__ . '/sharingan2.jpeg');
     * @param $uploadfile
     * @param $maxwidth
     * @param $maxheight
     * @param $name
     */
    function ResizeImage($uploadfile, $maxwidth, $maxheight, $name)
    {
        switch (strtolower(pathinfo($uploadfile, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                $uploadfile = imagecreatefromjpeg($uploadfile);
                break;
            case 'png':
                $uploadfile = imagecreatefrompng($uploadfile);
                break;
        }
        //取得当前图片大小
        $width = imagesx($uploadfile);
        $height = imagesy($uploadfile);
        $i = 0.5;
        //生成缩略图的大小
        if (($width > $maxwidth) || ($height > $maxheight)) {
            $newwidth = $width * $i;
            $newheight = $height * $i;
            if (function_exists("imagecopyresampled")) {
                $uploaddir_resize = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            } else {
                $uploaddir_resize = imagecreate($newwidth, $newheight);
                imagecopyresized($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }

            ImageJpeg($uploaddir_resize, $name);
            ImageDestroy($uploaddir_resize);
        } else {
            ImageJpeg($uploadfile, $name);
        }
    }
}