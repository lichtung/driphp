<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/9/6
 * Time: 17:18
 */

namespace {

    use driphp\Kernel;

    const DRI_PROJECT_NAME = 'driphp';
    require __DIR__ . '/../../driphp/bootstrap.php';

    Kernel::getInstance()->init()->start();
}