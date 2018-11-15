<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/15
 * Time: 17:33
 */

namespace driphp\core;


class Kits
{
    /**
     * 获取本地时间
     * @return string
     */
    public static function getLocalDatetime(): string
    {
        return (new \DateTime('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s');
    }
}