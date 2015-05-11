<?php

require_once ROOT_PATH . '/vendor/autoload.php';

class IndexController extends My_Controller_Action_Abstract {



    public function init()

    {
    
       if(count(Zend_Auth::getInstance()->getIdentity()) > 0) {

           $this->_redirect(BASE_PATH."home");

       }

       $this->credit = 5;

    }



    public function indexAction() {
		$config = Zend_Registry::get('config_global');
		$bootstrap = $this->getInvokeArg('bootstrap');
		$userAgent = $bootstrap->getResource('useragent');
		$userAgent->getDevice();

		if ($userAgent->getBrowserType() === 'mobile')
		{
			$this->view->layout()->setLayout('mobile');
			$this->_helper->viewRenderer->setViewSuffix('mobile.html');

			return true;
		}

        $this->view->layout()->setLayout('login');

        $response = new stdClass();

        $request = $this->getRequest();

        $userTable = new Application_Model_User();

        $emailInvites = new Application_Model_Emailinvites();

        $newsFactory = new Application_Model_NewsFactory();

        $errors = array();

        $data = array();

        if ($this->_request->isPost()) {
          
            $userTable->validateData($request, $data, $errors);

            if (empty($errors)) {


                $data['Password'] = hash('sha256', $data['Password']);

                $data['address']  = $data['Location'];

                $data['latitude']  = $request->getParam('RLatitude');

                $data['longitude'] = $request->getParam('RLongitude');

                $data['Conf_code'] = $newsFactory->generateCode();
                $data['regType'] = 'herespy';


                if ($row = $newsFactory->registration($data)) {

                    $this->view->activate_url = BASE_PATH . "index/reg-confirm/id/" . $row->id . "/q/" . $row->Conf_code;
                    // Code to sending mail to reciever

                    $this->to = $row->Email_id;

                    $this->subject = "SeeAround.me Registration";

                    $this->from = $config->email->from_email . ':' . $config->email->from_name;

                    $firstLoginResponseData = $this->firstLoginAction($data);

                    $this->message = $this->view->action("confirmation", "general", array());

                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                    //@mail($to, $subject, $message, $headers);


                    $this->_redirect(BASE_PATH . "home/index");
                } else {

                    $this->view->errors = "errors";
                }
            } else {

                $this->view->errors = $errors;
            }
        }

		$geolocation = My_Ip::geolocation();

		$this->view->RLatitude = $this->_request->getPost('RLatitude', $geolocation[0]);
		$this->view->RLongitude = $this->_request->getPost('RLongitude', $geolocation[1]);

        if ($this->request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));
        }
    }
    
   
   
    public function firstLoginAction($data) {

        $data = array('email' => $data['Email_id'], 'pass' => $data['Password']);

        $newsFactory = new Application_Model_NewsFactory();

        $loginStatus = new Application_Model_Loginstatus();

        $inviteStatus = new Application_Model_Invitestatus();

        $returnvalue = $newsFactory->loginDetail($data);

        if ((count($returnvalue) > 0)) {

            $response = new stdClass();
            $auth = Zend_Auth::getInstance();

            $loginRow = $loginStatus->setData(array(user_id => $returnvalue->id, login_time => date('Y-m-d H:i:s'), ip_address => $_SERVER['REMOTE_ADDR']));

            $authData['user_id'] = $returnvalue->id;

            $authData['login_id'] = $loginRow->id;

            $authData['is_fb_login'] = false;

            $authData['user_name'] = $returnvalue->Name;

            $authData['user_email'] = $returnvalue->Email_id;

            $authData['latitude'] = $returnvalue->latitude;

            $authData['longitude'] = $returnvalue->longitude;

            $authData['pro_image'] = $returnvalue->Profile_image;

            $authData['address'] = $returnvalue->address;

            $auth->getStorage()->write($authData);

            $response->error = 0;
            $response->redirect = BASE_PATH . "home";
            return $response;
        }
    } 

   public function profileAction(){ 
   
        // action body

   }
    
    
  public function wsRegistrationAction() {
	  $config = Zend_Registry::get('config_global');
        $response = new stdClass();
        $request  = $this->getRequest();
        $userTable    = new Application_Model_User();
        $emailInvites = new Application_Model_Emailinvites();
        $newsFactory  = new Application_Model_NewsFactory();
        $loginStatus = new Application_Model_Loginstatus();
        $inviteStatus = new Application_Model_Invitestatus();
        

          $data = array();
         
           $data['Name'] = $_REQUEST['Name'];
           $data['Email_id'] = $_REQUEST['Email_id'];
           $data['Password'] = md5($_REQUEST['Password']);
           $data['Location'] = $_REQUEST['Location'];
           $data['address'] = $_REQUEST['address'];
           $data['latitude'] = $_REQUEST['latitude'];
           $data['longitude'] = $_REQUEST['longitude'];
           $data['Conf_code'] = '';
           $data['regType'] = 'herespy';
           $data['Status'] = 'active'; 

         
         if($userTable->isUserEmailExist(trim($data['Email_id']))){
                 $emailAlreadyExist = new stdClass();
                 $emailAlreadyExist->status = "SUCCESS";
                 $emailAlreadyExist->message= "User with this Email already exists";
                 $emailAlreadyExist->result = "No Result";
                 echo(json_encode($emailAlreadyExist)); exit;
         }

          if($row = $newsFactory->mobileRegistration($data)){
               $this->to      = $row->Email_id;
               $this->subject = "seearound.me new Registration";
               $this->from = $config->email->from_email . ':' . $config->email->from_name;
               $this->message = 'Thank you for registring with us.';
               $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
          
               $successMessage = array('Registration Successfull');
               $successMessage = json_encode($successMessage);
               if(isset($successMessage)){
                     $data1   = array();
                     $data1['email'] =  $data['Email_id'];
                     $data1['pass']  =  $data['Password'];
                     $getUserId = $newsFactory->getUserId($data1);
                      
                     /*If login succesful then create a token */
                      $token = md5(uniqid($username,true));
                       try{ 
                         $tokenUpdation = $newsFactory->updateToken($token,$getUserId['id']);
                       } catch(Exception $e) {
                         print_r($e);
                       }
                        
                     /*End Token creation */
                      $returnvalue = array();
                      $returnvalue = $newsFactory->loginDetail($data1);
                      if((count($returnvalue) > 0) && ($returnvalue->Status == "active")) {
                      $loginRow = $loginStatus->setData(array(user_id=>$returnvalue->id, login_time=>date('Y-m-d H:i:s'), ip_address=>$_SERVER['REMOTE_ADDR'])); 
                      $returnvalue  = $returnvalue->toArray();
                      $returnvalue['login_id'] =  $loginRow->id;
                     // die(Zend_Json_Encoder::encode($returnvalue));
                      $response = new stdClass();
                      $response->status  = "SUCCESS";
                      $response->message = "Registration Successfull";
                      $response->result  = $returnvalue; 
                      echo(json_encode($response)); exit;
                    
                    } else {
                       /* $failureMessage = array('Not Authenticated');
                          $failureMessage = json_encode($failureMessage);
                          print_r($failureMessage); exit; */
                        $failureMessage = new stdClass();
                        $failureMessage->status = "FAILED";
                        $failureMessage->message= "User Not Authenticated";
                        $failureMessage->result = $returnvalue;
                        echo(json_encode($failureMessage)); exit;
                    }

                }
           } else {
              /* $failureMessage = array('Registration Not Successfull');
                 $failureMessage = json_encode($failureMessage);
                 print_r($failureMessage); exit; */
                 $failureMessage = new stdClass();
                 $failureMessage->status = "FAILED";
                 $failureMessage->message= "Registration Not Successfull";
                 $failureMessage->result = "No Result";
                 echo(json_encode($failureMessage)); exit;
           }
     }

	/**
	 * Login action.
	 *
	 * @return void
	 */
	public function loginAction()
	{
		try
		{
			$email = $this->_request->getPost('email');

			if (My_Validate::emptyString($email))
			{
				throw new RuntimeException('Email cannot be blank', -1);
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				throw new RuntimeException('Incorrect email address format: ' . var_export($email, true), -1);
			}

			$password = $this->_request->getPost('password');

			if (My_Validate::emptyString($password))
			{
				throw new RuntimeException('Password cannot be blank', -1);
			}

			$newsFactory = new Application_Model_NewsFactory;
			$returnvalue = $newsFactory->loginDetail(array(
				'email' => $email,
				'pass' => hash('sha256', $password)
			));

			if (!$returnvalue)
			{
				throw new RuntimeException('Invalid email or password', -1);
			}

			$response = array();

			if ($returnvalue->Status == 'active')
			{
				$loginStatus = new Application_Model_Loginstatus;

				$loginRow = $loginStatus->setData(array(
					'user_id' => $returnvalue->id,
					'login_time' => new Zend_Db_Expr('NOW()'),
					'ip_address' => $_SERVER['REMOTE_ADDR']
				));

				Zend_Auth::getInstance()->getStorage()->write(array(
					'user_id' => $returnvalue->id,
					'login_id' => $loginRow->id,
					'is_fb_login' => false,
					'user_name' => $returnvalue->Name,
					'user_email' => $returnvalue->Email_id,
					'latitude' => $returnvalue->latitude,
					'longitude' => $returnvalue->longitude,
					'pro_image' => $returnvalue->Profile_image,
					'address' => $returnvalue->address
				));

				// TODO: ???
				if (date('D') == 'Mon')
				{
					$loginRows = $loginStatus->sevenDaysOldData($returnvalue->id);
					$inviteCount = floor(count($loginRows) / $this->credit);
					$inviteStatusRow = Application_Model_Invitestatus::getInstance()->getData(array('user_id' => $returnvalue->id));

					if ($inviteStatusRow && floor((time() - strtotime($inviteStatusRow->updated)) / (24 * 60 * 60)) >= 7)
					{
						$inviteStatusRow->invite_count = $inviteStatusRow->invite_count + $inviteCount;
						$inviteStatusRow->updated = new Zend_Db_Expr('NOW()');
						$inviteStatusRow->save();
					}
				}

				if ($this->_request->getPost('remember'))
				{
					Zend_Session::rememberMe();
				}

				$response['active'] = 1;
				$response['redirect'] = BASE_PATH . 'home';
			}
			else
			{
				$response['active'] = 0;
				$response['redirect'] = BASE_PATH . 'index/reg-success/id/' . $returnvalue->id;
			}

			$response['status'] = 1;
		}
		catch (RuntimeException $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e->getMessage())
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

	/**
	 * Facebook login action.
	 *
	 * @return void
	 */
	public function fbLoginAction()
	{
		$config = Zend_Registry::get('config_global');

		Facebook\FacebookSession::setDefaultApplication($config->facebook->app->id, $config->facebook->app->secret);

		$session = (new Facebook\FacebookJavaScriptLoginHelper())->getSession();

		if (!$session)
		{
			throw new Exception('Login error.');
		}

		$me = (new Facebook\FacebookRequest(
		  $session, 'GET', '/me'
		))->execute()->getGraphObject(Facebook\GraphUser::className());

		$email = $me->getEmail();

		if (!$email)
		{
			throw new Exception('Email not activated');
		}

		$user_model = new Application_Model_User;

		$network_id = $me->getId();

		$user = $user_model->findByNetworkId($network_id);

		if (!$user)
		{
			$user = $user_model->findByEmail($email);

			if ($user)
			{
				$user_model->update(
					array('Network_id' => $network_id),
					$user_model->getAdapter()->quoteInto('id =?', $user->id)
				);
			}
			else
			{
				$user = $user_model->createRow(array(
					'Network_id' => $network_id,
					'Name' => $me->getName(),
					'Email_id' => $email,
					'Status' => 'active',
					'Creation_date'=> new Zend_Db_Expr('NOW()'),
					'Update_date' => new Zend_Db_Expr('NOW()')
				));

				$me_picture = (new Facebook\FacebookRequest(
					$session, 'GET', '/me/picture', array('type' => 'square', 'redirect' => false)
				))->execute()->getGraphObject();

				$picture = $me_picture->getProperty('url');

				if ($picture != null)
				{
					$user->Profile_image = $me_picture->getProperty('url');
				}

				$user->save();
				
				Application_Model_Profile::getInstance()->insert(array(
					'user_id' => $user->id,
					'Gender' => ucfirst($me->getGender())
				));

				$geolocation = My_Ip::geolocation();

				Application_Model_Address::getInstance()->insert(array(
					'user_id' => $user->id,
					'latitude' => $geolocation[0],
					'longitude' => $geolocation[1]
				));

				Application_Model_Invitestatus::getInstance()->insert(array(
					'user_id' => $user->id,
					'created' => new Zend_Db_Expr('NOW()'),
					'updated' => new Zend_Db_Expr('NOW()')
				));

				$users = Application_Model_Fbtempusers::getInstance()->findAllByNetworkId($network_id);

				if (count($users))
				{
					$users_model = new Application_Model_Friends;

					foreach($users as $tmp_user)
					{
						$users_model->insert(array(
							'sender_id' => $tmp_user->sender_id,
							'reciever_id' => $user->id,
							'cdate' => new Zend_Db_Expr('NOW()'),
							'udate' => new Zend_Db_Expr('NOW()')
						));

						$tmp_user->delete();
					}
				}
			}
		}

		$status_model = new Application_Model_Loginstatus;

		$loginRow = $status_model->setData(array(
			'user_id' => $user->id,
			'login_time' => new Zend_Db_Expr('NOW()'),
			'ip_address' => $_SERVER['REMOTE_ADDR']
		));

		$auth = Zend_Auth::getInstance();

		if ($auth->hasIdentity())
		{
			$auth->clearIdentity();
		}

		$auth->getStorage()->write(array(
			'user_id' => $user->id,
			'login_id' => $loginRow->id,
			'is_fb_login' => true,
			'user_name' => $user->Name,
			'user_email' => $user->Email_id,
			'latitude' => $user->lat(),
			'longitude' => $user->lng(),
			'pro_image' => $user->Profile_image,
			'address' => $user->address(),
			'network_id' => $user->Network_id
		));

		// TODO: ???
		if (date('D') == 'Mon')
		{
			$loginRows = $status_model->sevenDaysOldData($user->id);
			$inviteCount = floor(count($loginRows) / $this->credit);
			$inviteStatusRow = Application_Model_Invitestatus::getInstance()->getData(array('user_id' => $user->id));

			if ($inviteStatusRow && floor((time() - strtotime($inviteStatusRow->updated)) / (24 * 60 * 60)) >= 7)
			{
				$inviteStatusRow->invite_count = $inviteStatusRow->invite_count + $inviteCount;
				$inviteStatusRow->updated = new Zend_Db_Expr('NOW()');
				$inviteStatusRow->save();
			}
		}

		$this->_redirect(BASE_PATH . 'home');
	}

    public function registerAction()

    {

        // action body

    }

	/**
	 * Inactive account action.
	 *
	 * @return void
	 */
	public function regSuccessAction()
	{
		if (!Application_Model_User::checkId($this->_request->getParam('id'), $user) || $user->Status != 'inactive')
		{
			throw new Exception('Incorrect user ID');
		}

		$this->view->layout()->setLayout('login');
		$this->view->headScript()->appendScript('	var user = ' . json_encode(array('id' => $user->id)) . ';');
	}

    public function regConfirmAction()

    {

        $this->view->layout()->setLayout('login');

        if($this->getRequest()->isPost() || $this->getRequest()->isGet()) {

            $newsFactory = new Application_Model_NewsFactory();

            $id = $this->getRequest()->getParam('id', null);

            $code = $this->getRequest()->getParam('q', null);

            if($row = $newsFactory->confirmEmail($id, $code)) {

                $this->view->success = 'Email confirm success';

            }else {

                $this->view->eroors ="Inactive link";

            }

        }	

    }

	/**
	 * Resend user account activate email action.
	 *
	 * @return void
	 */
	public function resendAction() 
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('id'), $user) || $user->Status != 'inactive')
			{
				throw new RuntimeException('Incorrect user ID');
			}

			My_Email::send(
				array($user->Name => $user->Email_id),
				'Re-send activation link',
				array(
					'template' => 'user-resend',
					'assign' => array('user' => $user)
				)
			);

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

    

    public function forgotAction()

    {
		$config = Zend_Registry::get('config_global');

        $this->view->layout()->setLayout('login');

        $tableUser = new Application_Model_User;

        $newsFactory = new Application_Model_NewsFactory();

        if($this->_request->isPost()) {

            if($email = $this->_request->getPost("email", null)) {

                if($row = $tableUser->getUsers(array("Email_id" => $email))) {

                    $row->Status    = "inactive";

                    $row->Conf_code = $newsFactory->generateCode();

                    $row->save();

                    $this->to   = $row->Email_id;

                    $this->from = $config->email->from_email . ':' . $config->email->from_name;

                    $this->view->forgot_url  = BASE_PATH."index/change-password/pc/yes/em/".urlencode($row->Email_id)."/cd/".urlencode($row->Conf_code);

                    $this->subject = "Forgot Password";

                    $this->view->name      = $row->Name;

                    $this->view->adminName = "Admin";

                    $this->view->response  = "seearound.me";

                    $this->message         = $this->view->action("forgot-password", "general", array());

                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                    //@mail($to, $subject, $message, $headers); 

                    $this->view->forgotsuccess = "done";

                } else {

                    $this->view->forgoterror = "Sorry! No account found with that email address."; 

                } 

            } else {

                $this->view->forgoterror = "Please enter email id."; 

            }

        }

    }

    

    public function changePasswordAction() 

    {

        $this->view->layout()->setLayout('login');

        if(isset($this->auth['user_id'])) {

            $this->_redirect(BASE_PATH);    

        }

        $erorrs = array();

        $this->view->email = $email = $this->request->getParam("em", null);

        $this->view->code = $code = $this->request->getParam("cd", null);

        if($this->_request->isPost()) { 

            $tableUser = new Application_Model_User;

            $this->view->email = $email = $this->request->getPost("email", null);

            $this->view->code = $code   = $this->request->getPost("code", null);

            $password = $this->request->getPost("password", null);

            $repassword = $this->request->getPost("re-password", null);

            //echo strlen($password), strlen($repassword); exit;

            if($password == "") { 

                $this->view->pass = "Palese enter new password";

            }else if($password != $repassword) {  

                $this->view->pass = "New password and re-password not matched";

            }else { 

                $select = $tableUser->select()

                    ->where('Email_id =?', $email)

                    ->where('Conf_code =?', $code);

                if($row = $tableUser->fetchRow($select)) {

                    $insData = array(

                        'Password'  => md5($password),

                        'Conf_code' => new Zend_Db_Expr("NULL"),

                        'Status'    => "active"

                    );

                    $row->setFromArray($insData);

                    $row->save();

                    $this->view->success = "Done";

                }else { 

                   $this->view->inactive = "This is an inactive link"; 

                }    

            }

        }

    }

        

    public function sendInvitationAction()

    {

        $this->view->layout()->setLayout('login');

        $request = $this->getRequest();

        $userTable = new Application_Model_User();

        $newsFactory = new Application_Model_NewsFactory();

        $emailInvites = new Application_Model_Emailinvites();

        $errors = array();

        $data = array();

        $this->view->country = $newsFactory->countriesList();

        $this->view->states = $newsFactory->stateList();    

        

        if($this->_request->isPost()) {

            $usertable->validateData($request, $data, $errors); 

            if(empty($errors)) {

                $email = $data['Email_id'];

                $code = $newsFactory->generateCode();

                $invitationData = array(

                    'sender_id'     => new Zend_Db_Expr("NULL"),

                    'receiver_email'=>  new Zend_Db_Expr("NULL"),

                    'code'          => $code,

                    'self_email'    => $email,

                    'created'       => date('Y-m-d H:i:s')

                );	

                $is_Saved = $emailInvites->saveInvitationInfo(array('self_email'=>$email),$invitationData);

                if($is_Saved){

                    // Code to sending mail to reciever

               

                    $invitation_message = "invitation sent";

                    $response->success = $invitation_message;

                } else{

                    $invitation_message = "An invitation has been sent already on this email."; 

                    $response->errors = $invitation_message;

                }

            } else {

                //$this->view->errors = $errors;

                if(($errors['Email_id']=="This information is required.")){

                    $errors['Email_id'] ="Invalid email entered.";

                }

                $invitation_message = $errors['Email_id'];    

                $response->errors = $invitation_message;

            }		

        }

        

        if($this->getRequest()->isGet() && $this->_request->getParam('q') != '' && $this->_request->getParam('regType') != '') {

            if($emailRow = $emailInvites->getData(array('code'=> $this->_request->getParam('q')))) {

                $this->view->regEmail = ($emailRow->receiver_email)?($emailRow->receiver_email):($emailRow->self_email);

            }                      

        } else {

            if($this->_request->getParam('regCode')) {

                

            } else {

                $this->_redirect(BASE_PATH);

                $this->_redirect(BASE_PATH);

            }

        }

        

    }
}
