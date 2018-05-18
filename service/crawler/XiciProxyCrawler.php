<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/5/18 0018
 * Time: 17:36
 */

namespace sharin\service\crawler;


use sharin\core\FileSystem;
use sharin\core\Log;
use sharin\core\request\Curl;

/**
 * Class XiciProxyCrawler
 * @method XiciProxyCrawler getInstance(array $config = []) static
 * @package sharin\service\crawler
 */
class XiciProxyCrawler extends ProxyCrawler
{
    public function internalHttpPool(): string
    {
        return 'http://www.xicidaili.com/wt';
    }

    public function internalHttpFetchRegular(): string
    {
        return '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})</td>\s+<td>(\d{1,5})</td>';
    }

    public function internalHttpsPool(): string
    {
        return '';
    }

    public function internalCommonPool(): string
    {
        return '';
    }

    public function internalGhostPool(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function requestHttpPool(): array
    {
        list($code, $content) = Curl::get($this->internalHttpPool(), '', false, [], [
            'User-Agent: ' . UserAgentGenerator::getInstance()->random(),
        ]);
        if ($code > 299 or empty($content)) {
            Log::getLogger('crawler')->fatal([$code, $content]);
        } else {
            if (preg_match_all('#' . $this->internalHttpFetchRegular() . '#', $content, $matches)) {
                $length = count($matches[0]);
                $ips = $matches[1] ?? [];
                $ports = $matches[2] ?? [];
                $results = [];
                for ($i = 0; $i < $length; $i++) {
                    $results[] = [
                        $ips[$i],
                        $ports[$i],
                    ];
                }
                return $results;
            } else {
                Log::getLogger('crawler')->fatal('nothing matched');
            }
        }
        return [];
    }


}