<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 9:08
 */
declare(strict_types=1);


echo 'OB level on start : ' . ob_get_level() . '<br>';

function output(string $str, bool $obAuto = true, bool $sobAuto = true)
{
    echo 'OB level on output : ' . ob_get_level() . '<br>';
    for ($j = 0; $j < 5; $j++) {
        echo $str . '<br>'; //str_repeat()是将一个字符串重复n次
        $obAuto and ob_flush(); //将数据从php的buffer中释放出来
        $sobAuto and flush(); //将释放出来的数据发送给浏览器
        sleep(1); //一秒钟后继续执行
    }
}

# 情况0 ，正常输出
//output('.', true, true);


# 情况1，关闭ob缓存，任何输出都会直接刷到系统SOB里面，即便不调用ob_flush()也能持续刷出
//ob_end_clean(); # 之前的输出 "OB level on start : 1 " 不见了
//output('.', false, true);

# 情况2。echo的内容长度大于SOB，注释掉flush() 也能得到同样的效果
output(str_repeat('.', 7996), false, false);


echo 'OB level on stop : ' . ob_get_level() . '<br>';