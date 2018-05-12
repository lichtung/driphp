<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 15:31
 */
declare(strict_types=1);


namespace sharin\service\email;


class Email
{

    /**
     * 标题
     * @var string
     */
    private $title = '';
    /**
     * 信件内容
     * @var string
     */
    private $body = '';
    /**
     * 发送者账号
     * @var string
     */
    private $sender = '';
    /**
     * 发送者名称
     * @var string
     */
    private $senderName = '';
    /**
     * 抄送地址
     * @var string
     */
    private $replyto = '';
    /**
     * 抄送人名称
     * @var string
     */
    private $replytoName = '';
    /**
     * 接受者
     * @var array
     */
    private $receiver = [];

    public function __construct(string $title = 'Hello', string $body = 'This is email for test!')
    {
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * 添加收件人地址
     * @param string $receiver 收件人地址
     * @param string $name 收件人名称,默认为空
     * @return Email
     */
    public function addAddress(string $receiver, string $name = ''): Email
    {
        $this->receiver[] = [$receiver, $name];
        return $this;
    }

    /**
     * @return string
     */
    public function getReplytoName(): string
    {
        return $this->replytoName;
    }

    /**
     * @param string $replytoName
     */
    public function setReplytoName(string $replytoName)
    {
        $this->replytoName = $replytoName;
    }

    /**
     * @return string
     */
    public function getReplyto(): string
    {
        return $this->replyto;
    }

    /**
     * @param string $replyto
     */
    public function setReplyto(string $replyto)
    {
        $this->replyto = $replyto;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     */
    public function setSender(string $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }

    /**
     * @param string $senderName
     */
    public function setSenderName(string $senderName)
    {
        $this->senderName = $senderName;
    }

    /**
     * @return array
     */
    public function getReceiver(): array
    {
        return $this->receiver;
    }
}