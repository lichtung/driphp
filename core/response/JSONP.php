<?php
/**
 * User: linzhv@qq.com
 * Date: 28/04/2018
 * Time: 21:47
 */
declare(strict_types=1);


namespace sharin\core\response;


use sharin\core\Response;

/**
 * Class JSONP (JSON with Padding)
 *
 *
 * @package sharin\core\response
 */
class JSONP extends Response
{

    public function __construct(array $data)
    {
        $this->output = ($_GET['callback'] ?? 'callback') . '(' . json_encode($data) . ')';
    }
}