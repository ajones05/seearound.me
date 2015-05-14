<?php



class GeneralController extends  My_Controller_Action_Abstract

{

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
		$data = $auth->getIdentity();

		if ($data)
		{
			$status = Application_Model_Loginstatus::getInstance()->find($data['login_id'])->current();

			if ($status)
			{
				$status->logout_time = date('Y-m-d H:i:s');
				$status->save();
			}

			$auth->clearIdentity();
		}

		$this->_redirect($this->view->baseUrl('/'));
    }

    public function inviteSelfAction()

    {   
		$config = Zend_Registry::get('config_global');
        $emailInvites  = new Application_Model_Emailinvites();

        $emailToInvite = $emailInvites->returnEmailInvites();

        if($emailToInvite){

            foreach ($emailToInvite as $row) {

                $url = $this->serverUrl() . $this->view->baseUrl("index/send-invitation/regType/email/q/".$row->code);

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
