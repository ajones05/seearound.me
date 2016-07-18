<?php
use Respect\Validation\Validator as v;

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
		if (Zend_Auth::getInstance()->hasIdentity())
		{
			$this->_redirect('/');
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

		$this->view->layout()->setLayout('beta');
		$this->_helper->viewRenderer->setNoRender(true);
	}

	/**
	 * Login action.
	 *
	 * @return void
	 */
	public function loginAction()
	{
		$settings = (new Application_Model_Setting)->findValuesByName([
			'google_mapsKey'
		]);

		$config = Zend_Registry::get('config_global');
		$loginForm = new Application_Form_Login;
		$addressForm = new Application_Form_Address;
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
			$userModel = new Application_Model_User;
			$data = $this->_request->getPost();

			if ($this->_request->get('isLogin'))
			{
				if ($loginForm->isValid($data))
				{
					$user = $userModel->findByEmail($loginForm->email->getValue());
					$password = $loginForm->password->getValue();

					// TODO: password_verify($password, $user->password_hash)
					if ($user && $user->Password === hash('sha256', $password))
					{
						if (!$user->password_hash)
						{
							$userModel->update(['password_hash' => password_hash($password, PASSWORD_BCRYPT)],
								'id=' . $user->id);
						}

						if ($user->Status !== 'active')
						{
							$this->_redirect($this->view->baseUrl('index/reg-success/id/' . $user->id));
						}

						$login_id = (new Application_Model_Loginstatus)->insert([
							'user_id' => $user->id,
							'login_time' => new Zend_Db_Expr('NOW()'),
							'visit_time' => new Zend_Db_Expr('NOW()'),
							'ip_address' => $_SERVER['REMOTE_ADDR']
						]);

						$auth = Zend_Auth::getInstance();
						$auth->getStorage()->write([
							'user_id' => $user->id,
							'login_id' => $login_id
						]);

						$remember = $loginForm->remember->getValue();
						Zend_Session::rememberMe($remember == 1 ?
							1209600 : // 2 weeks
							604800 // 1 week
						);

						if (date('N') == 1)
						{
							$user->updateInviteCount();
						}

						$this->_redirect('/');
					}
					else
					{
						$loginForm->addError('Incorrect user email or password');
					}
				}
			}
			else
			{
				$validAddress = $addressForm->isValid($data);
				$validProfile = $reg_form->isValid($data);

				if ($validAddress)
				{
					if ($validProfile)
					{
						$user = $userModel->register([
							'Conf_code' => My_CommonUtils::generateCode(),
							'Status' => 'inactive'
						]+$data);

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
							'login_time' => new Zend_Db_Expr('NOW()'),
							'visit_time' => new Zend_Db_Expr('NOW()'),
							'ip_address' => $_SERVER['REMOTE_ADDR'])
						);

						Zend_Auth::getInstance()->getStorage()->write(array(
							"user_id" => $user->id,
							"login_id" => $login_id
						));

						$this->_redirect($this->view->baseUrl('/'));
					}

					$this->view->headScript('script', 'var formData=' . json_encode([
						'address' => Application_Model_Address::format($data),
						'latitude' => $addressForm->latitude->getValue(),
						'longitude' => $addressForm->longitude->getValue()
					]) . ';');
				}
			}
		}

		$this->view->layout()->setLayout('login');
		$this->view->login_form = $loginForm;
		$this->view->reg_form = $reg_form;
		$this->view->addressForm = $addressForm;

		$this->view->headScript()
			->appendScript('var	geolocation=' . json_encode(My_Ip::geolocation()) . ',' .
				'timizoneList=' . json_encode(My_CommonUtils::$timezone) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3&libraries=places&key=' .
				$settings['google_mapsKey']);
	}

	/**
	 * Facebook login action.
	 *
	 * @return void
	 */
	public function fbAuthAction()
	{
		$facebookApi = My_Facebook::getInstance();
		$facebookHelper = $facebookApi->getJavaScriptHelper();
		$accessToken = $facebookHelper->getAccessToken();
		$facebookApi->setDefaultAccessToken($accessToken);

		$user = (new Application_Model_User)->facebookAuthentication($facebookApi);
		$login_id = (new Application_Model_Loginstatus)->insert(array(
			'user_id' => $user->id,
			'login_time' => new Zend_Db_Expr('NOW()'),
			'visit_time' => new Zend_Db_Expr('NOW()'),
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
		$id = $this->_request->getParam('id');

		if (!v::intVal()->validate($id))
		{
			throw new RuntimeException('Incorrect user ID value: ' .
				var_export($id, true));
		}

		if (!Application_Model_User::checkId($id, $user) ||
			$user->Status != 'inactive')
		{
			throw new RuntimeException('Incorrect user ID');
		}

		$this->view->layout()->setLayout('login');
		$this->view->headScript()->appendScript('var user=' .
			json_encode(['id' => $user->id]) . ';');
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
			$id = $this->_request->getParam('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_User::checkId($id, $user) || $user->Status != 'inactive')
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($id, true));
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

	/**
	 * Forgot password action.
	 *
	 * @return void
	 */
	public function forgotAction()
	{
		$this->view->layout()->setLayout('login');

		$form = new Application_Form_Forgot;

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();

			if ($form->isValid($data))
			{
				$user = Application_Model_User::findByEmail($data['email']);

				if (!$user)
				{
					$form->email->addError('Sorry! No account found with that email address.');
					$form->markAsError();
				}

				if ($user->Status !== 'active')
				{
					$form->email->addError('Sorry! this account is not active.');
					$form->markAsError();
				}

				if (!$form->isErrors())
				{
					$confirmModel = new Application_Model_UserConfirm;
					$confirm = $confirmModel->save([
						'user_id' => $user->id,
						'type_id' => $confirmModel::$type['forgot_password']
					]);

					My_Email::send(
						$user->Email_id,
						'Forgot Password',
						['template' => 'forgot-password', 'assign' => ['confirm' => $confirm]]
					);

					$this->_redirect($this->view->baseUrl('forgot-success'));
				}
			}
		}

		$this->view->form = $form;
	}

	/**
	 * Forgot password success action.
	 *
	 * @return void
	 */
	public function forgotSuccessAction()
	{
		$this->view->layout()->setLayout('login');
	}

	/**
	 * Change password action.
	 *
	 * @return void
	 */
	public function changePasswordAction()
	{
		$this->view->layout()->setLayout('login');

		$code = $this->_request->getParam('code');

		if (!v::stringType()->length(1, 12)->validate($code))
		{
			throw new RuntimeException('Incorrect confirm code value: ' .
				var_export($code, true));
		}

		$confirmModel = new Application_Model_UserConfirm;
		$confirm = $confirmModel->findByCode($code, false);

		if ($confirm == null)
		{
			throw new RuntimeException('Incorrect confirm code: ' .
				var_export($code, true));
		}

		$form = new Application_Form_UserPassword;

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();

			if ($form->isValid($data))
			{
				$user = $confirm->findDependentRowset('Application_Model_User')->current();
				$user->Password = hash('sha256', $data['password']);
				$user->password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
				$user->save();

				$confirmModel->updateDelete($confirm);

				$this->_redirect($this->view->baseUrl('change-password-success'));
			}
		}

		$this->view->form = $form;
	}

	/**
	 * Change password success action.
	 *
	 * @return void
	 */
	public function changePasswordSuccessAction()
	{
		$this->view->layout()->setLayout('login');
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
