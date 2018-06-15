<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/24 0024
 * Time: 14:40
 */

namespace driphp\service\monitor;

/**
 * Class ProcessMemory
 *
 * ps -e -o 'pid,comm,args,pcpu,rsz,vsz,stime,user,uid'
 *
 *
 * 　　$ ps -e -o 'pid,comm,args,pcpu,rsz,vsz,stime,user,uid'  其中rsz是是实际内存
 * 　　$ ps -e -o 'pid,comm,args,pcpu,rsz,vsz,stime,user,uid' | grep oracle |  sort -nrk5
 *
 * @package driphp\service\monitor
 */
class ProcessMemory
{

}