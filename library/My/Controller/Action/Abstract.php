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
        $this->view->emailLogin =  $this->_request->getCookie('emailLogin');
        $this->view->passwordLogin =  $this->_request->getCookie('passwordLogin');
        $this->view->publicProfile = $this->profile;
    }
    
    public function _init() 
    {
        $this->to = "admin@herespy.com";
        $this->from = "Admin";
        $this->subject = "Here spy admin";
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

    public function getFullURL() {
        $host  = $_SERVER['HTTP_HOST'];
        $proto = (empty($_SERVER['HTTPS'])) ? 'http' : 'https';
        return $proto . '://' . $host.Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
    }

    public function setBaseUrl($url = '', $addModuleName = true) {
        if ($addModuleName) $url = '/' . $this->getRequest()->getParam('module') . $url;
        Zend_Controller_Front::getInstance()->setBaseUrl($url);
    }

    public function _generateCaptcha($config = array())
    {
        $captcha = new Zend_Form_Element_Captcha('captcha', array(
            'captcha' => array(	
            'captcha' => 'Image',
            'wordLen' => 5,
            'font' => LIB_DIR . '/fonts/DejaVuSans.ttf',
            'imgDir' => realpath(".").'/public/www/captcha',
            'imgUrl' => $this->getBaseURL().'/public/www/captcha',
            'timeout' => 300,
            'width' => 150,
            'height' => 80,
            'dotNoiseLevel' => 50,
            'lineNoiseLevel' => 10,
            'fontSize' => 30
            )
        ));

        $captcha = $captcha->render();
        return $captcha;
    }

    public function postDispatch() {
		parent::postDispatch();
    }
    
}