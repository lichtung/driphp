<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/24 0024
 * Time: 14:41
 */

namespace sharin\service\monitor;

/**
 * Class Top
 *
 * 查看某某账户的内存使用情况
 *  top -u [username]
 *
 * 　  PID：进程的ID
 * 　　USER：进程所有者
 * 　　PR：进程的优先级别，越小越优先被执行
 * 　　NInice：值
 * 　　VIRT：进程占用的虚拟内存
 * 　　RES：进程占用的物理内存
 * 　　SHR：进程使用的共享内存
 * 　　S：进程的状态。S表示休眠，R表示正在运行，Z表示僵死状态，N表示该进程优先值为负数
 * 　　%CPU：进程占用CPU的使用率
 * 　　%MEM：进程使用的物理内存和总内存的百分比
 * 　　TIME+：该进程启动后占用的总的CPU时间，即占用CPU使用时间的累加值。
 * 　　COMMAND：进程启动命令名称
 *
 * @package sharin\service\monitor
 */
class Top
{

}