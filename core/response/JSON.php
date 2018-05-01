<?php
/**
 * User: linzhv@qq.com
 * Date: 11/04/2018
 * Time: 23:07
 */
declare(strict_types=1);


namespace sharin\core\response;


use sharin\core\Response;

class JSON extends Response
{
    protected function __construct(array $data, $options)
    {
        $this->setHeader('Content-Type', 'application/json;charset=utf-8');
        $this->output = json_encode($data, $options);
    }

}