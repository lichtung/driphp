<?php
/**
 * Created by PhpStorm.
 * User: v.linzh
 * Date: 2018/4/18
 * Time: 11:50
 */
require __DIR__ . '/public/index.php';
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet(\sharin\core\Database::getInstance()->getEntityManager());