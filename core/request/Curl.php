<?php
/**
 * User: linzhv@qq.com
 * Date: 12/05/2018
 * Time: 16:16
 */
declare(strict_types=1);


namespace driphp\core\request;


use driphp\core\FileSystem;
use driphp\throws\io\network\PageNotFoundException;
use driphp\throws\io\NetworkException;

/**
 * Class Curl
 * @see http://docs.guzzlephp.org/en/stable/
 * @package driphp\core\request
 */
class Curl
{

    /**
     * @param string $url
     * @param string $cookie
     * @param bool $header
     * @param array $opts
     * @param array $rq_header
     * @param bool $jsonReturn
     * @return mixed|string
     */
    public static function get(string $url, $cookie = '', $header = false, array $opts = [], array $rq_header = [], bool $jsonReturn = false)
    {
        $ch = curl_init($url);
        if (stripos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36');
        curl_setopt($ch, CURLOPT_HEADER, $header); //将头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rq_header and curl_setopt($ch, CURLOPT_HTTPHEADER, $rq_header);
        $jsonReturn and curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8',]);
        if ($cookie) {
            if (strpos($cookie, '/') === 0) {
                # linux下绝对路径
                FileSystem::touch($cookie);
            }
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        }
        if ($opts) foreach ($opts as $k => $v) {
            curl_setopt($ch, $k, $v);
        }

        $content = (string)curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $content];
    }


    /**
     * @param $url
     * @param $fields
     * @param string $cookie
     * @param bool $header
     * @param array $opts
     * @param array $rq_header
     * @return string
     * @throws NetworkException
     */
    public static function post($url, $fields, $cookie = '', $header = false, array $opts = [], array $rq_header = [])
    {
        $ch = curl_init($url);
        if (stripos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($ch, CURLOPT_HEADER, $header); //将头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $rq_header and curl_setopt($ch, CURLOPT_HTTPHEADER, $rq_header);
        if ($cookie) {
            if (strpos($cookie, '/') === 0) {
                FileSystem::touch($cookie);
            }
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        }
        if ($opts) foreach ($opts as $k => $v) {
            curl_setopt($ch, $k, $v);
        }

        $content = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 404) {
            throw new PageNotFoundException($url);
        }
        if (false === $content) {
            throw new NetworkException($url);
        }
        return (string)$content;
    }

    /**
     * @param string $url
     * @param string|array $data
     * @return array
     * @throws NetworkException
     */
    public static function postJson(string $url, $data): array
    {
        $ch = curl_init();
        if (strpos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
        ]);
        $response = curl_exec($ch);
        if ((int)curl_getinfo($ch, CURLINFO_HTTP_CODE) === 404) {
            throw new PageNotFoundException($url);
        }
        if (false === $response) {
            throw new NetworkException(curl_error($ch));
        } else {
            $resdata = json_decode($response, true);
            if (!$resdata) {
                throw new NetworkException(var_export($response, true));
            } else {
                return $resdata;
            }
        }
    }

}