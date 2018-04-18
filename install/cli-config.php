<?php
/**
 * Created by PhpStorm.
 * User: v.linzh
 * Date: 2018/4/18
 * Time: 11:50
 */

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use sharin\core\Database;

require __DIR__ . '/public/index.php';
return ConsoleRunner::createHelperSet(Database::getInstance()->getEntityManager());