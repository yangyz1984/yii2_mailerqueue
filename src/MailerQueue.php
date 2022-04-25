<?php
/**
 * Created by PhpStorm.
 * User: YYZ
 * Date: 2022/4/24 21:55
 * Desc:
 */

namespace zhaohui\mailerqueue;
use Yii;

class MailerQueue extends \yii\swiftmailer\Mailer
{
    public $messageClass = "zhaohui\mailerqueue\Message"; //调用compose方法时读取这个文件
    public $key = 'mails';
    public $db = '1';

    /*发送邮件*/
    public function process()
    {
        $redis = Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found in config.');
        }
        //如果队列中 存在数据
        if ($redis->select($this->db) && $messages = $redis->lrange($this->key, 0, -1)) {
            $messageObj = new Message;
            //遍历邮件列表
            foreach ($messages as $message) {
                $message = json_decode($message, true);
                if (empty($message) || !$this->setMessage($messageObj, $message)) {
                    throw new \ServerErrorHttpException('message error');
                }
                if ($messageObj->send()) {
                    $redis->lrem($this->key, -1, json_encode($message));
                }
            }
        }
        return true;
    }

    //设置消息头部
    public function setMessage($messageObj, $message)
    {
        if (empty($messageObj)) {
            return false;
        }
        if (!empty($message['from']) && !empty($message['to'])) {
            $messageObj->setFrom($message['from'])->setTo($message['to']);
            if (!empty($message['cc'])) {
                $messageObj->setCc($message['cc']);
            }
            if (!empty($message['bcc'])) {
                $messageObj->setBcc($message['bcc']);
            }
            if (!empty($message['reply_to'])) {
                $messageObj->setReplyTo($message['reply_to']);
            }
            if (!empty($message['charset'])) {
                $messageObj->setCharset($message['charset']);
            }
            if (!empty($message['subject'])) {
                $messageObj->setSubject($message['subject']);
            }
            if (!empty($message['html_body'])) {
                $messageObj->setHtmlBody($message['html_body']);
            }
            if (!empty($message['text_body'])) {
                $messageObj->setTextBody($message['text_body']);
            }
            return $messageObj;
        }
        return false;
    }
}