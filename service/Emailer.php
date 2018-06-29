<?php
/**
 * User: linzhv@qq.com
 * Date: 2018/2/27 0027
 * Time: 15:28
 */
declare(strict_types=1);


namespace driphp\service;

use PHPMailer\PHPMailer\PHPMailer;
use driphp\Component;
use driphp\core\Logger;
use driphp\service\email\Email;
use driphp\throws\service\EmailException;

/**
 * Class Emailer
 *
 * @see https://github.com/PHPMailer/PHPMailer
 * @method Emailer getInstance(string $index = '') static
 *
 * @package driphp\service
 */
class Emailer extends Component
{
    protected $config = [
        # 主要账号
        'master.host' => 'smtp.qq.com',
        'master.user' => '820693502@qq.com',
        'master.passwd' => 'tjhexpfyuugwbfdf',
        # 备用账号
        'spare.host' => '',
        'spare.user' => '',
        'spare.passwd' => '',
    ];

    protected function initialize()
    {
    }

    /**
     * 发送邮件
     * @param Email $email
     * @return bool
     * @throws
     */
    public function send(Email $email): bool
    {
        $master_conf = [
            'host' => $this->config['master.host'],
            'user' => $this->config['master.user'],
            'passwd' => $this->config['master.passwd'],
        ];
        try {
            $this->post($email, $this->getSmtpClient($master_conf));
            return true;
        } catch (EmailException $exception) {
            Logger::getLogger('email')->emergency([
                'type' => 'master.error',
                'master.conf' => $master_conf,
                'error' => $exception->getMessage(),
            ]);
            $this->_error = $exception->getMessage();
            $spare_conf = [
                'host' => $this->config['spare.host'],
                'user' => $this->config['spare.user'],
                'passwd' => $this->config['spare.passwd'],
            ];

            try {
                $this->post($email, $this->getSmtpClient($spare_conf));
                $this->_error = '';
                return true;
            } catch (EmailException $exception) {
                Logger::getLogger('email')->emergency([
                    'type' => 'spare.error',
                    'master.conf' => $spare_conf,
                    'error' => $this->_error = $exception->getMessage(),
                ]);
            }
        }
        return false;
    }

    /**
     *
     * 设置smtp服务器
     *
     * 其配置必须包含下面字段
     * [
     *  'host'=>'smtp服务器地址,如smtp.163.com,smtp.qq.com',
     *  'user'=>'账户名称',
     *  'passwd'=>'密码',
     * ]
     *
     * @param array $config
     * @return PHPMailer
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function getSmtpClient(array $config): PHPMailer
    {
        static $_instances = [];
        ksort($config);
        $feature = md5(serialize($config));
        if (!isset($_instances[$feature])) {
            $instance = new PHPMailer();
            //$instance->SMTPDebug = 3;                                                         // Enable verbose debug output
            $instance->isSMTP();                                      // Set mailer to use SMTP
            $instance->Host = $config['host'];  // Specify main and backup SMTP servers
            $instance->Username = $config['user'];                 // SMTP username
            $instance->Password = $config['passwd'];                           // SMTP password ()
            $instance->SMTPAuth = true;                               // Enable SMTP authentication
            $instance->SMTPSecure = $config['secure'] ?? 'ssl';  // Enable TLS encryption, `ssl` also accepted
            $instance->Port = $config['port'] ?? 25;    // TCP port to connect to
            $instance->setFrom($config['user'], $config['name'] ?? 'Message Center');
            $_instances[$feature] = $instance;
        }
        return $_instances[$feature];
    }

    /**
     * @param Email $email 邮件实体
     * @param PHPMailer $mailer 邮件发送对象
     * @return void
     * @throws EmailException 邮件发送失败时抛出
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function post(Email $email, PHPMailer $mailer)
    {
        //设置收件人
        foreach ($email->getReceiver() as $receiver) {
            $mailer->addAddress($receiver[0], $receiver[1]);
        }
        //设置抄送
        if ($replyto = $email->getReplyto()) {
            $mailer->addReplyTo($replyto, $email->getReplytoName());
        }

        $mailer->isHTML(true);                                  // Set email format to HTML
        $mailer->Subject = $email->getTitle();
        $mailer->Body = $email->getBody();
        $mailer->AltBody = '';

        if (!$mailer->send()) throw new EmailException((string)$mailer->ErrorInfo);
    }

}