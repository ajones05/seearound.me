<?php



class GeneralController extends  My_Controller_Action_Abstract

{



    public function init()

    {

        /* Initialize action controller here */

        $this->view->request = $this->getRequest();

    }



    public function indexAction()

    {
      


    }

    public function confirmationAction()
    {
    }

    public function forgotPasswordAction()
    {
    }

	public function messageNotificationAction()
	{
	}

    public function logoutAction()

    {

        $auth = Zend_Auth::getInstance();

        $authData = $auth->getIdentity();

        $loginStatus = new Application_Model_Loginstatus();

        $row = $loginStatus->find($authData['login_id'])->current();

        if($row) {

            $row->logout_time = date('Y-m-d H:i:s');

            $row->save();

        }

        $auth->clearIdentity();
		$this->_redirect(BASE_PATH);
    }

    public function inviteSelfAction()

    {   
		$config = Zend_Registry::get('config_global');
        $emailInvites  = new Application_Model_Emailinvites();

        $emailToInvite = $emailInvites->returnEmailInvites();

        if($emailToInvite){

            foreach ($emailToInvite as $row) {

                $url = $url = BASE_PATH."index/send-invitation/regType/email/q/".$row->code;

                $this->to = $row->self_email;

                $this->subject = "seearound.me Invitation";

                $this->from = $config->email->from_email . ':' . $config->email->from_name;

                $this->view->name = $row->self_email;

                

                $message = "To join seearound.me, please click the link below!<br />".$url;



                $this->view->message = "<p align='justify'>$message</p>";

                $this->view->adminName = "Admin";

                $this->view->response = "seearound.me";

                $this->message = $this->view->action("index","general",array());

                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                $row->status = '1';

                $row->save();

                

            }

        }

        die();

    }

    

}
