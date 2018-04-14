<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 22:28
 */
declare(strict_types=1);


namespace sharin\core\interfaces;

use Throwable;

interface ExceptionHandlerInterface
{

    public function error(int $code, string $message, string $file, int $line);

    public function exception(Throwable $e);

}