<?php

class My_Mail
{
	/**
	 * Email address for sending email
	 * 
	 * @var string
	 */
	var $to = '';
	
	/**
	 * Class constructor
	 *
	 * @param string $to email address
	 */
	public function __construct($to) {
		$this->to = $to;
	}
	
	/**
	 * Sending email
	 * 
	 * @param string $subject
	 * @param text $message
	 */
	public function send($subject, $message, $attachment) {
	foreach($this->to as $mailonebyone) { 
		$mailConfig = Zend_Registry::get('config_global')->email;
		$mail = new Zend_Mail($mailConfig->charset);
		$mail->setFrom($mailConfig->from_email, $mailConfig->from_name);
		$mail->addTo($mailonebyone);
		$mail->setSubject($subject);
		$mail->setBodyText(strip_tags($message));
		$mail->setBodyHtml($message);
		if ($attachment) {
			$mail->addAttachment($attachment);
		}
	    $mail->send();
	 }
	}
}