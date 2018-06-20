<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2018/6/20 0020
 * Time: 12:17
 */

namespace driphp\service\elastic;


use driphp\throws\ParameterInvalidException;

/**
 * Class Document
 * @package driphp\service\elastic
 */
class Document
{
    /** @var array 原始数据 */
    private $raw;
    /** @var string 索引 */
    private $index;
    /** @var string 类型 */
    private $type;
    /** @var string ID */
    private $id;
    /** @var int 相关度 */
    private $score;
    /** @var array 数据 */
    private $source;
    /** @var string 路径 */
    private $path;

    public function __construct(array $data)
    {
        $this->raw = $data;
        $this->index = $data['_index'] ?? '';
        $this->type = $data['_type'] ?? '';
        $this->id = $data['_id'] ?? '';
        $this->score = $data['_score'] ?? 0;
        $this->source = $data['_source'] ?? [];
        $this->path = "/{$this->index}/{$this->type}/{$this->id}";
    }

    /**
     * @param array $list
     * @return  Document[]
     * @throws ParameterInvalidException
     */
    public static function parseFromList(array $list): array
    {
        $data = [];
        foreach ($list as $item) {
            $item = self::parseFromItem($item);
            $data[$item->getPath()] = $item;
        }
        return $data;
    }

    /**
     * @param $data
     * @return Document
     * @throws ParameterInvalidException
     */
    public static function parseFromItem($data): Document
    {
        if (is_array($data) and isset($data['_source'])) {
            return new Document($data);
        } else {
            throw new ParameterInvalidException(var_export($data, true));
        }
    }

    /**
     * @return mixed|string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return mixed|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array|mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

}