<?php
/**
 * Index controller class.
 * Handles index actions.
 */
class IndexController extends Zend_Controller_Action
{
    /**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
	public function init()
	{
		if (count(Zend_Auth::getInstance()->getIdentity()) > 0)
		{
			$this->_redirect($this->view->baseUrl('/'));
		}

		parent::init();
	}

	/**
	 * Index action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		if ((new Mobile_Detect)->isMobile())
		{
			$this->view->layout()->setLayout('mobile');
			$this->_helper->viewRenderer->setViewSuffix('mobile.html');

			return true;
		}

		$login_form = new Application_Form_Login;
		$reg_form = new Application_Form_Registration;
        $reg_form->addElement(
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
			$nowTime = (new DateTime)->format(DateTime::W3C);
			$data = $this->_request->getPost();

			if ($this->_request->get('isLogin'))
			{
				if ($login_form->isValid($data))
				{
					$user = (new Application_Model_User)->findByEmail($login_form->email->getValue());

					if ($user && $user->Password === hash('sha256', $login_form->password->getValue()))
					{
						if ($user->Status !== 'active')
						{
							$this->_redirect($this->view->baseUrl('index/reg-success/id/' . $user->id));
						}

						$login_id = (new Application_Model_Loginstatus)->insert(array(
							'user_id' => $user->id,
							'login_time' => $nowTime,
							'visit_time' => $nowTime,
							'ip_address' => $_SERVER['REMOTE_ADDR'])
						);

						Zend_Auth::getInstance()->getStorage()->write(array(
							'user_id' => $user->id,
							'login_id' => $login_id
						));

						if (date('N') == 1)
						{
							$user->updateInviteCount();
						}

						if ($login_form->remember->getValue())
						{
							Zend_Session::rememberMe();
						}

						$this->_redirect($this->view->baseUrl('/'));
					}
					else
					{
						$login_form->addError('Incorrect user email or password');
					}
				}
			}
			else
			{
				if ($reg_form->isValid($data))
				{
					$user = (new Application_Model_User)->register(
						array_merge(
							$reg_form->getValues(),
							array(
								'Conf_code' => My_CommonUtils::generateCode(),
								'Status' => 'inactive'
							)
						)
					);

					My_Email::send(
						$user->Email_id,
						'SeeAround.me Registration',
						array(
							'template' => 'registration',
							'assign' => array('user' => $user)
						)
					);

					$login_id = (new Application_Model_Loginstatus)->insert(array(
						'user_id' => $user->id,
						'login_time' => $nowTime,
						'visit_time' => $nowTime,
						'ip_address' => $_SERVER['REMOTE_ADDR'])
					);

					Zend_Auth::getInstance()->getStorage()->write(array(
						"user_id" => $user->id,
						"login_id" => $login_id
					));

					$this->_redirect($this->view->baseUrl('/'));
				}
				else
				{
					if ($reg_form->getErrors('latitude') || $reg_form->getErrors('longitude'))
					{
						$reg_form->address->addError('Incorrect user location');
					}
				}
			}
		}

		$this->view->layout()->setLayout('login');
		$this->view->login_form = $login_form;
		$this->view->reg_form = $reg_form;

		$this->view->headScript()
			->appendScript("	var	geolocation = " . json_encode(My_Ip::geolocation()) . ";\n")
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places');
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
			throw new RuntimeException('Incorrect facebook access token');
		}

		$user = (new Application_Model_User)->facebookAuthentication($session);

		$nowTime = (new DateTime)->format(DateTime::W3C);

		$login_id = (new Application_Model_Loginstatus)->insert(array(
			'user_id' => $user->id,
			'login_time' => $nowTime,
			'visit_time' => $nowTime,
			'ip_address' => $_SERVER['REMOTE_ADDR'])
		);

		if (date('N') == 1)
		{
			$user->updateInviteCount();
		}

		Zend_Auth::getInstance()->getStorage()->write(array(
			'user_id' => $user->id,
			'login_id' => $login_id
		));

		$this->_redirect($this->view->baseUrl('/'));
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

        if($this->_request->isPost()) {

            if($email = $this->_request->getPost("email", null)) {

                if($row = $tableUser->getUsers(array("Email_id" => $email))) {

                    $row->Status = "inactive";
                    $row->Conf_code = My_CommonUtils::generateCode();
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
		$this->view->layout()->setLayout('login');
        $this->view->email = $email = $this->_request->getParam("em", null);
        $this->view->code = $code = $this->_request->getParam("cd", null);

        if ($this->_request->isPost())
		{ 

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

                        'Password'  => hash('sha256', $password),

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
        $emailInvites = new Application_Model_Emailinvites;

		if ($this->_request->isPost())
		{
			try
			{
				$email = $this->_request->getPost('Email_id');

				if (My_Validate::emptyString($email))
				{
					throw new RuntimeException('Email cannot be blank', -1);
				}

				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					throw new RuntimeException('Incorrect email address format: ' . var_export($email, true), -1);
				}

				if (Application_Model_User::findByEmail($email))
				{
					throw new RuntimeException('This email is already registered with seearound.me', -1);
				}

				$result = $emailInvites->saveInvitationInfo(
					array('self_email' => $email),
					array(
						'sender_id' => new Zend_Db_Expr("NULL"),
						'receiver_email'=>  new Zend_Db_Expr("NULL"),
						'code' => My_CommonUtils::generateCode(),
						'self_email' => $email,
						'created' => new Zend_Db_Expr('NOW()')
					)
				);

				if (!$result)
				{
					throw new RuntimeException('An invitation has been sent already on this email', -1);
				}

				$response = array('status' => 1);
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
		$this->view->layout()->setLayout('login');
	}
}
