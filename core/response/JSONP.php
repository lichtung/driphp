<?php
/**
 * User: linzhv@qq.com
 * Date: 28/04/2018
 * Time: 21:47
 */
declare(strict_types=1);


namespace driphp\core\response;


use driphp\core\Response;

/**
 * Class JSONP (JSON with Padding)
 *
 *
 * @package driphp\core\response
 */
class JSONP extends Response
{

    public function __construct(array $data)
    {
        $this->output = ($_GET['callback'] ?? 'callback') . '(' . json_encode($data) . ')';
    }
}