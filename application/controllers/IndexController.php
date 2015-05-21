<?php

require_once ROOT_PATH . '/vendor/autoload.php';

class IndexController extends Zend_Controller_Action {

	public function init()
	{
		if (count(Zend_Auth::getInstance()->getIdentity()) > 0)
		{
			$this->_redirect($this->view->baseUrl("home"));
		}

		$this->credit = 5;
	}

	/**
	 * Index action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$userAgent = $this->getInvokeArg('bootstrap')->getResource('useragent');
		$userAgent->getDevice();

		if ($userAgent->getBrowserType() === 'mobile')
		{
			$this->view->layout()->setLayout('mobile');
			$this->_helper->viewRenderer->setViewSuffix('mobile.html');

			return true;
		}

		$form = new Application_Form_Registration;

        $form->addElement(
			'password',
			'repassword',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 50)),
					array('Identical', false, array('token' => 'password'))
				)
			)
		);

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();

			if ($form->isValid($data))
			{
				$newsFactory = new Application_Model_NewsFactory;

				$user = (new Application_Model_User)->register(
					array_merge(
						$form->getValues(),
						array(
							'Conf_code' => $newsFactory->generateCode(),
							'Status' => 'inactive'
						)
					)
				);

				My_Email::send(
					$row->Email_id,
					'SeeAround.me Registration',
					array(
						'template' => 'registration',
						'assign' => array('user' => $user)
					)
				);

				$login_id = (new Application_Model_Loginstatus)->insert(array(
					'user_id' => $user->id,
					'login_time' => new Zend_Db_Expr('NOW()'),
					'ip_address' => $_SERVER['REMOTE_ADDR'])
				);

				Zend_Auth::getInstance()->getStorage()->write(array(
					"user_id" => $user->id,
					"login_id" => $login_id
				));

				$this->_redirect($this->view->baseUrl("home/index"));
			}
		}

		if ($form->latitude->getValue() == '' || $form->longitude->getValue() == '')
		{
			$geolocation = My_Ip::geolocation();
			$form->latitude->setValue($geolocation[0]);
			$form->longitude->setValue($geolocation[1]);
		}

		$this->view->layout()->setLayout('login');
		$this->view->form = $form;

		$this->view->headScript()
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places');
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

			$user = (new Application_Model_User)->findByEmail($email);

			if (!$user || $user->Password !== hash('sha256', $password))
			{
				throw new RuntimeException('Incorrect user email or password', -1);
			}

			$response = array('status' => 1);

			if ($user->Status == 'active')
			{
				$loginStatus = new Application_Model_Loginstatus;
				$login_id = $loginStatus->insert(array(
					'user_id' => $user->id,
					'login_time' => new Zend_Db_Expr('NOW()'),
					'ip_address' => $_SERVER['REMOTE_ADDR'])
				);

				Zend_Auth::getInstance()->getStorage()->write(array(
					'user_id' => $user->id,
					'login_id' => $login_id
				));

				// TODO: ???
				if (date('D') == 'Mon')
				{
					$loginRows = $loginStatus->sevenDaysOldData($user->id);
					$inviteCount = floor(count($loginRows) / $this->credit);
					$inviteStatusRow = Application_Model_Invitestatus::getInstance()->getData(array('user_id' => $user->id));

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
				$response['redirect'] = $this->view->baseUrl('home');
			}
			else
			{
				$response['active'] = 0;
				$response['redirect'] = $this->view->baseUrl('index/reg-success/id/' . $user->id);
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

		$this->_helper->json($response);
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

		$loginStatus = new Application_Model_Loginstatus;
		$login_id = $loginStatus->insert(array(
			'user_id' => $user->id,
			'login_time' => new Zend_Db_Expr('NOW()'),
			'ip_address' => $_SERVER['REMOTE_ADDR'])
		);

		Zend_Auth::getInstance()->getStorage()->write(array(
			'user_id' => $user->id,
			'login_id' => $login_id
		));

		// TODO: ???
		if (date('D') == 'Mon')
		{
			$loginRows = $loginStatus->sevenDaysOldData($user->id);
			$inviteCount = floor(count($loginRows) / $this->credit);
			$inviteStatusRow = Application_Model_Invitestatus::getInstance()->getData(array('user_id' => $user->id));

			if ($inviteStatusRow && floor((time() - strtotime($inviteStatusRow->updated)) / (24 * 60 * 60)) >= 7)
			{
				$inviteStatusRow->invite_count = $inviteStatusRow->invite_count + $inviteCount;
				$inviteStatusRow->updated = new Zend_Db_Expr('NOW()');
				$inviteStatusRow->save();
			}
		}

		$this->_redirect($this->view->baseUrl('home'));
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

                    $row->Status = "inactive";
                    $row->Conf_code = $newsFactory->generateCode();
                    $row->save();

					My_Email::send(
						$row->Email_id,
						'Forgot Password',
						array(
							'template' => 'forgot-password',
							'assign' => array('user' => $row)
						)
					);

                    $this->view->forgotsuccess = "done";

                } else {

                    $this->view->forgoterror = "Sorry! No account found with that email address."; 

                } 

            } else {

                $this->view->forgoterror = "Please enter email id."; 

            }

        }

		$this->view->email = $this->_request->getPost('email');
    }

    

    public function changePasswordAction() 
    {
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->_redirect($this->view->baseUrl('/'));    
		}

		$this->view->layout()->setLayout('login');

        $erorrs = array();

        $this->view->email = $email = $this->_request->getParam("em", null);

        $this->view->code = $code = $this->_request->getParam("cd", null);

        if($this->_request->isPost()) { 

            $tableUser = new Application_Model_User;

            $this->view->email = $email = $this->_request->getPost("email", null);

            $this->view->code = $code   = $this->_request->getPost("code", null);

            $password = $this->_request->getPost("password", null);

            $repassword = $this->_request->getPost("re-password", null);

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

                    'created'       => new Zend_Db_Expr('NOW()')

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

                $this->_redirect($this->view->baseUrl('/'));
            }

        }

		$this->view->email = $this->_request->getParam('Email_id');
    }
}
