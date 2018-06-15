<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 21:21
 */
declare(strict_types=1);


namespace driphp\library;

/**
 * Class Sphinx
 * TODO:
 *
 * indexer:
 * /usr/local/sphinx/bin/indexer --rotate $1 --config /home/asus/sphinx/sphinx.conf --all
 *
 * start:
 * echo -e "\n-------------------------------"
 * echo "-- The count of parameter is '$#';"
 * echo -e "-------------------------------\n"
 *
 * var_count=0
 * if  test ${#} -gt ${var_count}
 * then
 * echo -e "\n-- Try using config '$1'\n"
 * /usr/local/sphinx/bin/searchd -c $1
 * else
 * echo -e "\n-- Using default sphinx.conf '/home/asus/sphinx/sphinx.conf';\n"
 * /usr/local/sphinx/bin/searchd -c /home/asus/sphinx/sphinx.conf
 * fi
 *
 * stop:
 * /usr/local/sphinx/bin/searchd --stop
 *
 * @package driphp\library
 */
class Sphinx
{

}