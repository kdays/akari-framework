<?php
namespace Akari\utility;

!defined("AKARI_PATH") && exit;

import("core.external.PHPMailer.phpmailer");

Class SendMail{
	public static $options = Array(
		"Charset" => 'utf-8',
		"Host" => 'smtp.qq.com',
		'Port' => 465,
		
		'SMTPAuth' => true,
		'SMTPSecure' => 'ssl',
			
		'AltBody' => 'To view the message, please use an HTML compatible email viewer!'
	);
	public static $ErrorMessage = false;

	/**
	 * 传递Mail上的参数
	 *
	 * @param string $key 键名
	 * @param string $value 内容
	 */
	public static function setOption($key, $value){
		self::$options[$key] = $value;
	}

	/**
	 * 邮件发送
	 *
	 * @param MailModel $m 邮件模型
	 */
	public static function send(MailModel $m){
		return self::_send(
			$m->address, 
			$m->subject, $m->content, 
			$m->receiverName, 
			$m->displayName
		);
	}
	
	/**
	 * 发送邮件
	 *
	 * @param string $address 电邮地址
	 * @param string $subject 邮件标题
	 * @param string $content 邮件内容
	 * @param string $user 接受者名称
	 * @param string $senderName 发送人显示名称
	 */
	public static function _send($address, $subject, $content, $user='User', $senderName='KDays'){
		$mailObj = self::createMailObject(self::$options['Username'], $senderName);
		
		$mailObj->Subject = $subject;
		$mailObj->AddAddress($address, $user);
		
		$mailObj->MsgHTML( eregi_replace("[\]" , '', $content) );
		
		if($mailObj->Send()){
			return true;
		}else{
			self::$ErrorMessage = $mailObj->ErrorInfo;
			
			return false;
		}
	}
	
	/**
	 * 根据options创建mail对象
	 *
	 * @param string $address 发送人地址
	 * @param string $senderName 发送人显示名称
	 * @return object PHPMailer对象
	 */
	 public static function createMailObject($address, $senderName){
	 	 $mail = new PHPMailer();
	 	 
	 	 $mail->IsSMTP();
	 	 $mail->SMTPDebug = 0;
	 	 $mail->SetFrom($address, $senderName);
	 	 
	 	 foreach(self::$options as $key => $value){
	 	 	 $mail->{$key} = $value;
	 	 }
	 	 
	 	 return $mail;
	 }
}