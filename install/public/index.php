<?php
/**
 * Created by PhpStorm.
 * User: v.linzh
 * Date: 2018/4/18
 * Time: 11:51
 */

use driphp\Kernel;
use driphp\core\Route;
const SR_PROJECT_NAME = 'demo';
require __DIR__ . '/../../driphp/bootstrap.php';
$kernel = Kernel::getInstance()->init([
]);
Route::group('test', function () {
    Route::get('products', [\controller\test\Database::class, 'showProductList']);
    Route::get('product/{id}', [\controller\test\Database::class, 'showProduct']);
    Route::post('product', [\controller\test\Database::class, 'createProduct']);
    Route::put('product/{id}', [\controller\test\Database::class, 'updateProduct']);

});
$kernel->start();