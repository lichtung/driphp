<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 20:53
 */
declare(strict_types=1);


namespace sharin\core;

use Closure;
use sharin\Component;
use sharin\core\cache\Redis;

/**
 * Class Cache 高速缓存（台湾译**快取**）
 *
 * @see https://www.zhihu.com/question/26190832
 *
 * cache 是为了弥补高速设备和低速设备的鸿沟而引入的中间层，最终起到**加快访问速度**的作用。
 * buffer 的主要目的进行流量整形，把突发的大数量较小规模的 I/O 整理成平稳的小数量较大规模的 I/O，以**减少响应次数**
 *      （比如从网上下电影，你不能下一点点数据就写一下硬盘，而是积攒一定量的数据以后一整块一起写，不然硬盘都要被你玩坏了；，Buffer的核心作用是用来缓冲，
 *      缓和冲击。比如你每秒要写100次硬盘，对系统冲击很大，浪费了大量时间在忙着处理开始写和结束写这两件事嘛。用个buffer暂存起来，变成每10秒写一次硬盘，
 *      对系统的冲击就很小，写入效率高了，日子过得爽了。极大缓和了冲击。）
 *
 * >>>
 *  1、Buffer（缓冲区）是系统两端处理速度平衡（从长时间尺度上看）时使用的。它的引入是为了减小短期内突发I/O的影响，起到流量整形的作用。
 *      比如生产者——消费者问题，他们产生和消耗资源的速度大体接近，加一个buffer可以抵消掉资源刚产生/消耗时的突然变化。
 *  2、Cache（缓存）则是系统两端处理速度不匹配时的一种折衷策略。因为CPU和memory之间的速度差异越来越大，所以人们充分利用数据的局部性（locality）特征，
 *      通过使用存储系统分级（memory hierarchy）的策略来减小这种差异带来的影响。
 *  3、假定以后存储器访问变得跟CPU做计算一样快，cache就可以消失，但是buffer依然存在。比如从网络上下载东西，瞬时速率可能会有较大变化，
 *      但从长期来看却是稳定的，这样就能通过引入一个buffer使得OS接收数据的速率更稳定，进一步减少对磁盘的伤害。
 *  4、TLB（Translation LookAside Buffer，翻译后备缓冲器）名字起错了，其实它是一个cache.
 * >>>
 *
 * PHP的flush()和ob_flush()的区别:
 *  void flush ( void )         - Flush system output buffer(SOB)
 *  void ob_flush ( void )      - Flush (send) the output buffer(OB)
 *
 * SOB(操作系统OB机制):
 *  Linux系统默认大小一般为4096(4kb)
 *  其主要用是存储速度不同步或者优先级不同的设备之间传处理数据的区域，可以使进程之间的相互等待变少。例如，当你打开一个编辑器，输入字符，
 *  操作系统并不会立即把这个字符直接写入到磁盘，而是先写入到buffer，当写满了一个buffer的时候，才会把buffer中的数据写入磁盘，当调用内核函数flush()
 *  (这里指的是linux内核函数)的时候，强制要求把buffer中的数据写回磁盘
 * OB(PHP自己的OB机制):
 *  默认是开启的，大小默认4096(4kb)，在php.ini配置文件中由output_buffering配置。当执行php执行echo,print的时候，是先将数据写入php的buffer，
 *  当一个php buffer写满的时候，脚本进程会将php 的buffer数据发送给系统内核交由tcp传给浏览器显示。
 * 浏览器Buffer:
 *  目前浏览器普遍为8000Bytes（可能用户可以设置，未亲测）,测试 Chrome与360极速模式为8000Bytes,只有输出数据达到了这个长度或者脚本结束浏览器才会将
 *  数据输出在页面上
 *
 * Data flow 数据流向：
 *  echo/print -> php buffer -> tcp buffer （服务器系统buffer）-> 浏览器 buffer ->浏览器展示
 *
 * @method void  set(string $key, mixed $value, int $ttl = 3600)
 * @method bool  has(string $key)
 * @method void  delete(string $key)
 * @method void  clean()
 *
 * @method Cache getInstance(array $config = []) static
 * @package sharin\core
 */
class Cache extends Component
{
    protected $config = [
        'drivers' => [
            [
                'name' => Redis::class,
                'config' => [
                    'host' => '127.0.0.1',
                    'secret' => '',
                    'password' => NULL,
                    'port' => 6379,
                    'timeout' => 7.0,
                    'database' => 0
                ],
            ],
        ],
    ];

    protected function initialize()
    {
    }

    /**
     * 获取缓存
     * @param string $name
     * @param Closure|mixed $replace 如果是一个闭包，则值不存在时获取并设置缓存
     * @param int $expire Closure返回值的缓存期
     * @return mixed
     * @throws \sharin\throws\core\ClassNotFoundException
     * @throws \sharin\throws\core\DriverNotDefinedException
     */
    public function get(string $name, $replace = null, $expire = 3600)
    {
        if ($replace instanceof Closure) {
            $value = $this->drive()->get($name, null);
            if (null === $value) {
                $this->drive()->set($name, $value = $replace(), $expire);
            }
        } else {
            $value = $this->drive()->get($name, $replace);
        }
        return $value;
    }
}