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

    public function friendInvitationAction()
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

        if($this->_request->getCookie('emailLogin') && $this->_request->getCookie('passwordLogin')) {

            setcookie("emailLogin", "", 0, '/');

            setcookie("passwordLogin", "", 0, '/');

            setcookie("keepLogin", "yes", time() +7*24*60*60, '/');

            $this->_redirect(BASE_PATH);

        } else {

            $this->_redirect(BASE_PATH);

        }

    }

    public function inviteSelfAction()

    {   

        $emailInvites  = new Application_Model_Emailinvites();

        $emailToInvite = $emailInvites->returnEmailInvites();

        if($emailToInvite){

            foreach ($emailToInvite as $row) {

                $url = $url = BASE_PATH."index/send-invitation/regType/email/q/".$row->code;

                $this->to = $row->self_email;

                $this->subject = "Here Spy Invitation";

                $this->from = 'admin@herespy.com:Admin';

                $this->view->name = $row->self_email;

                

                $message = "To join Here-Spy, please click the link below!<br />".$url;



                $this->view->message = "<p align='justify'>$message</p>";

                $this->view->adminName = "Admin";

                $this->view->response = "Here Spy";

                $this->message = $this->view->action("index","general",array());

                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                $row->status = '1';

                $row->save();

                

            }

        }

        die();

    }

    

}
