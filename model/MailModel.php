<?php
namespace Akari\model;

use Akari\utility\SendMail;

/**
 * Class MailModel
 * @view \Akari\utility\SendMail
 *
 * @package Akari\model
 */
Class MailModel {

    /**
     * 电邮地址
     * @var string
     */
    public $address;

    /**
     * 标题
     * @var string
     */
    public $subject;

    /**
     * 内容
     * @var string
     */
    public $content;

    /**
     * 接受者名称
     * @var string
     */
    public $receiverName;

    /**
     * 发送人显示名称
     * @var string
     */
    public $displayName;

    /**
     * @return bool
     */
    public function send() {
        return SendMail::send($this);
    }

}