<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/12
 * Time: 16:55
 */

namespace driphp\library\image;

/**
 * Class Resize
 * @package driphp\library\image
 */
class General
{
    /** @var string */
    private $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function resize($maxWidth, $maxHeight, $name)
    {
        $file = $this->file;
        switch (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                $file = imagecreatefromjpeg($file);
                break;
            case 'png':
                $file = imagecreatefrompng($file);
                break;
        }
        //取得当前图片大小
        $width = imagesx($file);
        $height = imagesy($file);
        $i = 0.5;
        //生成缩略图的大小
        if (($width > $maxWidth) || ($height > $maxHeight)) {
            $newWidth = $width * $i;
            $newHeight = $height * $i;
            if (function_exists("imagecopyresampled")) {
                $image = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($image, $file, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            } else {
                $image = imagecreate($newWidth, $newHeight);
                imagecopyresized($image, $file, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            }

            ImageJpeg($image, $name);
            ImageDestroy($image);
        } else {
            ImageJpeg($file, $name);
        }
    }
}