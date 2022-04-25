<?php
/**
 * Created by PhpStorm.
 * User: YYZ
 * Date: 2022/4/23 15:42
 * Desc:
 */

namespace zhaohui\mailerqueue;

class Message extends \yii\swiftmailer\Message
{
    public function queue()
    {
        $redis = \Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found config');
        }
        $mailer = \Yii::$app->mailer;
        if (empty($mailer) || !$redis->select($mailer->db)) {
            throw new \yii\base\InvalidConfigException('db not defined');
        }
        $message = [];
        $message['from'] = array_keys($this->getFrom());
        $message['to'] = array_keys($this->getTo());
        $message['cc'] = array_keys($this->getCc());
//        $message['bcc'] = array_keys($this->getBcc());
//        $message['reply_to'] = array_keys($this->getReplyTo());
        $message['charset'] = $this->getCharset();
        $message['subject'] = $this->getSubject();

        $parts = $this->getSwiftMessage()->getChildren();
        if (!is_array($parts) || !sizeof($parts)) {
            $parts = [$this->getSwiftMessage()];
        }

        foreach ($parts as $part) {
            if (!$part instanceof \Swift_Mime_Attachment) {
                //获取内容类型
                switch ($part->getContentType()) {
                    case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body'] = $part->getBody();
                        break;
                }
                if (!$message['charset']) {
                    $message['charset'] = $part->getCharset();
                }
            }
        }
        //序列化抓取的内容   存放到队列中
        return $redis->rpush($mailer->key, json_encode($message));
    }
}