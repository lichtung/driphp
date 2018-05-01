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
    /**
     * JSON constructor.
     * @param array $data
     * @param int $options JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR
     */
    public function __construct(array $data, int $options = JSON_ERROR_NONE)
    {
        $this->setHeader('Content-Type', 'application/json;charset=utf-8');
        $this->output = json_encode($data, $options);
    }

}