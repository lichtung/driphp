<?php
/**
 * Created by Linzh.
 * Email: linzhv@outlook.com
 * Date: 2018/11/29
 * Time: 20:14
 */

namespace driphp\service\manage;


use driphp\core\Cookie;
use driphp\core\Request;
use driphp\library\encrypt\AES;
use driphp\service\Service;

/**
 * Class Sign
 *
 * 根据token从数据库中读取对应的账户ID（缓存到redis客户端中）
 *
 * @method Sign factory() static
 * @package driphp\service\manage
 */
class Sign extends Service
{
    /** @var array */
    protected $config = [
        'key' => '123456',
        'method' => AES::METHOD_AES_256_CFB,
    ];
    /** @var AES */
    protected $encryptor;
    /** @var array */
    protected $signInfo;

    protected function initialize()
    {
    }

    protected function getEncryptor(): AES
    {
        if (!$this->encryptor) {
            $this->encryptor = AES::factory($this->config);
        }
        return $this->encryptor;
    }


    public function getUserId(): int
    {
        $access_token = Cookie::factory()->get('access_token');
        $this->signInfo = $this->parseAccessToken($access_token);
        return intval($this->signInfo['uid'] ?? 0);
    }

    /**
     * 创建access_token
     * @param int $uid 用户ID
     * @return string
     */
    public function generateAccessToken(int $uid)
    {
        $dataString = json_encode([
            'uid' => $uid,
            'ip' => Request::factory()->getClientIP(),
        ]);
        return $this->getEncryptor()->encrypt($dataString, true);
    }

    /**
     * 解析access_token
     * @param string $access_token
     * @return array
     */
    public function parseAccessToken(string $access_token): array
    {
        if ($access_token) {
            $access_token = $this->getEncryptor()->decrypt($access_token, true);
            $access_token = json_decode($access_token, true);
            return $access_token ?: [];
        } else {
            return [];
        }
    }

}