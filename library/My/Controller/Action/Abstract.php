<?php

abstract class My_Controller_Action_Abstract extends Zend_Controller_Action {
    
    protected $profile = false;
    public function preDispatch() 
    {
        parent::preDispatch();
        $this->view->request = $this->request = $this->getRequest();
        $this->view->auth = $this->auth = Zend_Auth::getInstance()->getIdentity();
        if(isset($this->auth['user_id']) && $this->auth['user_id'] !="") {
            $this->view->userId = $this->auth['user_id'];
            $this->view->isLogin = true;
        } else {
            $this->view->userId = '';
            $this->view->isLogin = false;
        }

        $this->view->publicProfile = $this->profile;
    }
    
    public function _init() 
    {
		$config = Zend_Registry::get('config_global');

        $this->to = $config->email->from_email;
        $this->from = $config->email->from_name;
        $this->subject = "seearound.me";
        $this->message = "";
        $this->attachments = null;
    }

    public function sendEmail($mto=null, $mfrom=null, $msubject=null ,$mmail_body="", $attachment=null) 
    {		
        if($mto) {
            $transport = new Zend_Mail_Transport_Smtp('localhost');
            $mailConfig = Zend_Registry::get('config_global')->email;
            $mail = new Zend_Mail($mailConfig->charset);
            if($mto) {
                $mail->addTo($mto);
            }else {
                $mail->addTo($this->to);
            }
            
            if($msebject) {
                $mail->setSubject($msubject);
            } else {
                $mail->setSubject($this->subject);
            }
            if($mfrom) {
                $mail->setFrom($mfrom);
            } else {
                $mail->setFrom($mailConfig->from_email, $mailConfig->from_name);
            }
            if($memail_body) {
                $mail->setBodyText(strip_tags($mmail_body));
                $mail->setBodyHtml($mmail_body);
            } else {
                $mail->setBodyText($this->message);
                $mail->setBodyHtml($this->message);
            }
            if ($attachment) {
                $mail->addAttachment($attachment);
            } 
            //@$mail->send($transport);
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            if(strpos($mfrom, ':')) {
                $mfrom = explode(':', $mfrom);
                if(is_array($mfrom) && count($mfrom) > 1) {
                    $headers .= "From: \"".$mfrom[1]."\" <".$mfrom[0].">\n";
                } 
            }else {
                $headers .= "From: $mfrom";
            }
            @mail($mto, $msubject, $mmail_body, $headers);
        }
    }

    public function getBaseURL($baseurl = null) 
    {
        $pre = BASE_PATH;
        if($baseurl) {
            return $pre;
        } else {
            return $pre.$baseurl;
        }

    }

    public function setBaseUrl($url = '', $addModuleName = true) {
        if ($addModuleName) $url = '/' . $this->getRequest()->getParam('module') . $url;
        Zend_Controller_Front::getInstance()->setBaseUrl($url);
    }
}
