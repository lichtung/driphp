<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/22 0022
 * Time: 21:58
 */
declare(strict_types=1);


namespace sharin\service;

use sharin\core\Service;
use OSS\Model\BucketInfo;
use OSS\OssClient;
use OSS\Core\OssException;
use sharin\throws\service\OSSException as AliyunOssException;

/**
 * Class OSS
 *
 * OSS - Object Storage Service
 * ACL - Access Control List
 *
 * @package application\common\library
 */
class Oss extends Service
{
    protected $config = [
        'access_key_id' => '',
        'access_key_secret' => '',
        'endpoint' => '',
        'bucket' => 'default',
    ];
    /**
     * @var OssClient OssClient instance
     */
    private $ossClient = null;
    /**
     * @var string Name of bucket
     */
    private $bucket = '';

    /**
     * @return $this|void
     * @throws AliyunOssException
     */
    protected function initialize()
    {
        parent::initialize();
        $this->bucket = $this->config['bucket'];
        try {
            $this->ossClient = new OssClient($this->config['access_key_id'], $this->config['access_key_secret'], $this->config['endpoint']);
        } catch (OssException $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * create a storage zone (bucket)
     *
     * @param string $bucket 要创建的bucket名字
     * @param string $acl Bucket access control
     *                              OSSClient::OSS_ACL_TYPE_PRIVATE
     *                              OssClient::OSS_ACL_TYPE_PUBLIC_READ
     *                              OssClient::OSS_ACL_TYPE_PUBLIC_READ_WRITE
     * @return void
     * @throws AliyunOssException
     */
    public function createBucket(string $bucket, string $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ)
    {
        try {
            $this->ossClient->createBucket($bucket, $acl);
        } catch (\Throwable $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * Set bucket ACL
     *
     * @param string $bucket bucket name
     * @param string $acl
     * @return bool Always return true
     * @throws AliyunOssException
     */
    function setBucketAcl(string $bucket, string $acl): bool
    {
        try {
            $this->ossClient->putBucketAcl($bucket, $acl);
            return true;
        } catch (OssException $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * Get bucket ACL
     *
     * @param string $bucket bucket name
     * @return bool
     * @throws AliyunOssException
     */
    function getBucketAcl($bucket): bool
    {
        try {
            $this->ossClient->getBucketAcl($bucket);
            return true;
        } catch (OssException $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * Check whether a bucket exists.
     * @param string $bucket bucket name
     * @return bool
     * @throws AliyunOssException
     */
    public function isBucketExist(string $bucket): bool
    {
        try {
            return $this->ossClient->doesBucketExist($bucket);
        } catch (OssException $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * Delete a bucket. If the bucket is not empty, the deletion fails.
     * A bucket which is not empty indicates that it does not contain any objects or parts that are not completely uploaded during multipart upload
     *
     * @param string $bucket Name of the bucket to delete
     * @return bool always return true
     * @throws AliyunOssException
     */
    function deleteBucket(string $bucket): bool
    {
        try {
            $this->ossClient->deleteBucket($bucket);
            return true;
        } catch (\Throwable $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * List all buckets
     * @return BucketInfo[]
     * @throws AliyunOssException
     */
    function listBuckets(): array
    {
        try {
            return (array)$this->ossClient->listBuckets()->getBucketList();
        } catch (OssException $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * @param string $object 对象/文件（Object）
     * @param string $content 文件内容或者文件路径，具体参考参数三
     * @param bool $isFile 是否是文件，false表示$content是文件内容，否则是上传文件路径
     * @return void
     * @throws AliyunOssException
     */
    public function upload(string $object, string $content, bool $isFile = false)
    {
        $object = trim($object, '\\/ ');
        try {
            if ($isFile) {
                if (!is_file($content) or !is_readable($content)) throw new AliyunOssException("File '$content' not exist or not readable");
                $this->ossClient->uploadFile($this->bucket, $object, $content);
            } else {
                $this->ossClient->putObject($this->bucket, $object, $content);
            }
        } catch (OssException $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }

    /**
     * @param string $object
     * @param string $saveAs 本地保存地址，为空时表示下载内容到内存并返回，否则下载内容保存到指定文件中并返回空字符串
     * @return string
     * @throws AliyunOssException
     */
    public function download(string $object, string $saveAs = '')
    {
        try {
            if ($saveAs) {
                $parentDir = dirname($saveAs);
                is_dir($parentDir) or mkdir($parentDir, 0777, true);
                if (is_file($saveAs)) throw new AliyunOssException("File '{$saveAs}' exist,cancel action");
                $content = $this->ossClient->getObject($this->bucket, $object, [
                    OssClient::OSS_FILE_DOWNLOAD => $saveAs,
                ]);
            } else {
                $content = $this->ossClient->getObject($this->bucket, $object);
            }
            return $content;
        } catch (\Throwable $e) {
            throw new AliyunOssException($e->getMessage());
        }
    }


}