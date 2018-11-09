<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/9
 * Time: 10:45
 */

namespace driphp\library\client\mongo;

/**
 * Interface IterateHandlerInterface 迭代过程处理器
 * @package driphp\library\client\mongo
 */
interface IterateHandlerInterface
{
    const CONTINUE = 1;
    const BREAK = 0;

    /**
     * @param array $data
     * @return bool 返回true表示继续,返回false表示终端遍历
     */
    public function handle(array $data): bool;

}