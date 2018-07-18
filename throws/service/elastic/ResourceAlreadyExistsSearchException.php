<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/7/17 0017
 * Time: 18:28
 */

namespace driphp\throws\service\elastic;


use driphp\throws\service\ElasticSearchException;

/**
 * Class ResourceAlreadyExistsSearchException 资源已经存在,重复创建时抛出
 *
 *
 * Elasticsearch\Common\Exceptions\BadRequest400Exception:
 * {
 *  "error": {
 *      "root_cause": [{
 *          "type": "resource_already_exists_exception",
 *          "reason": "index [hello_world/SuB3bFkZTa6IDyQsWCziPw] already exists",
 *          "index_uuid": "SuB3bFkZTa6IDyQsWCziPw",
 *          "index": "hello_world"
 *      }],
 *      "type": "resource_already_exists_exception",
 *      "reason": "index [hello_world/SuB3bFkZTa6IDyQsWCziPw] already exists",
 *      "index_uuid": "SuB3bFkZTa6IDyQsWCziPw",
 *      "index": "hello_world"
 *  },
 *  "status": 400
 * }
 * @package driphp\throws\service\elastic
 */
class ResourceAlreadyExistsSearchException extends ElasticSearchException
{
    public function getExceptionCode(): int
    {
        return 80001;
    }
}