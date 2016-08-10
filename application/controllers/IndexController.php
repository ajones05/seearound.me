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
		$layout = 'beta';

		if (My_CommonUtils::isMobile())
		{
			$layout .= '-mobile';
		}

		$this->view->layout()->setLayout($layout);
		$this->_helper->viewRenderer->setNoRender(true);
	}

	/**
	 * Login action.
	 *
	 * @return void
	 */
	public function loginAction()
	{
		$settings = Application_Model_Setting::getInstance();
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

					if ($user->password == null)
					{
						$this->_redirect('/forgot');
					}

					if ($user && password_verify($password, $user->password))
					{
						if ($user->Status !== 'active')
						{
							$this->_redirect($this->view->baseUrl('index/reg-success/id/' . $user->id));
						}

						$loginId = (new Application_Model_Loginstatus)->save($user);
						Application_Model_Invitestatus::updateCount($user);

						$auth = Zend_Auth::getInstance();
						$auth->getStorage()->write([
							'user_id' => $user->id,
							'login_id' => $loginId
						]);

						$remember = $loginForm->remember->getValue();
						Zend_Session::rememberMe($remember == 1 ?
							1209600 : // 2 weeks
							604800 // 1 week
						);

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
						$user = $userModel->register(['Status'=>'inactive']+$data);

						$confirmModel = new Application_Model_UserConfirm;
						$confirmCode = $confirmModel->generateConfirmCode();
						$confirmModel->insert([
							'user_id' => $user['id'],
							'type_id' => $confirmModel::$type['registration'],
							'code' => $confirmCode,
							'deleted' => 0,
							'created_at' => new Zend_Db_Expr('NOW()')
						]);

						My_Email::send(
							$user['Email_id'],
							'SeeAround.me Registration',
							[
								'template' => 'registration',
								'assign' => ['code' => $confirmCode],
								'settings' => $settings
							]
						);

						$loginId = (new Application_Model_Loginstatus)->save($user);

						Zend_Auth::getInstance()->getStorage()->write(array(
							"user_id" => $user['id'],
							"login_id" => $loginId
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
		$loginId = (new Application_Model_Loginstatus)->save($user);
		Application_Model_Invitestatus::updateCount($user);

		Zend_Auth::getInstance()->getStorage()->write(array(
			'user_id' => $user['id'],
			'login_id' => $loginId
		));

		$this->_redirect('/');
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

			if (!Application_Model_User::checkId($id, $user) ||
				$user['Status'] != 'inactive')
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($id, true));
			}

			$confirmModel = new Application_Model_UserConfirm;
			$confirmModel->deleteUserCode($user, $confirmModel::$type['registration']);

			$confirmCode = $confirmModel->generateConfirmCode();
			$confirmModel->insert([
				'user_id' => $user['id'],
				'type_id' => $confirmModel::$type['registration'],
				'code' => $confirmCode,
				'deleted' => 0,
				'created_at' => new Zend_Db_Expr('NOW()')
			]);

			My_Email::send(
				[$user['Name'] => $user['Email_id']],
				'Re-send activation link',
				[
					'template' => 'user-resend',
					'assign' => ['user' => $user, 'code' => $confirmCode]
				]
			);

			$response = ['status' => 1];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
		}

		$this->_helper->json($response);
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
					$confirmModel->deleteUserCode($user, $confirmModel::$type['password']);

					$confirmCode = $confirmModel->generateConfirmCode();
					$confirmModel->insert([
						'user_id' => $user['id'],
						'type_id' => $confirmModel::$type['password'],
						'code' => $confirmCode,
						'deleted' => 0,
						'created_at' => new Zend_Db_Expr('NOW()')
					]);

					My_Email::send(
						$user['Email_id'],
						'Forgot Password',
						[
							'template' => 'forgot-password',
							'assign' => ['code' => $confirmCode]
						]
					);

					$this->_redirect('forgot-success');
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

		$userModel = new Application_Model_User;
		$user = $userModel->findUserByPassCode($code);

		if ($user == null)
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
				$userModel->updateWithCache([
					'password' => $userModel->encryptPassword($data['password'])
				], $user);

				$confirmModel = new Application_Model_UserConfirm;
				$confirmModel->deleteUserCode($user, $confirmModel::$type['password']);

				$this->_redirect('change-password-success');
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
		$inviteForm = new Application_Model_Emailinvites;

		if ($this->_request->isPost())
		{
			try
			{
				$email = $this->_request->getPost('Email_id');

				if (!v::email()->validate($email))
				{
					throw new RuntimeException('Incorrect email address value: ' .
						var_export($email, true));
				}

				if (Application_Model_User::findByEmail($email))
				{
					throw new RuntimeException('This email is already registered with seearound.me');
				}

				$invite = $inviteForm->findByEmail($email);

				if ($invite != null)
				{
					throw new RuntimeException('An invitation has been sent already on this email');
				}

				$inviteForm->insert([
					'sender_id' => new Zend_Db_Expr("NULL"),
					'receiver_email'=>  new Zend_Db_Expr("NULL"),
					'code' => My_CommonUtils::generateCode(),
					'self_email' => $email,
					'created' => new Zend_Db_Expr('NOW()')
				]);

				$response = ['status' => 1];
			}
			catch (Exception $e)
			{
				$response = [
					'status' => 0,
					'error' => ['message' => $e instanceof RuntimeException ?
						$e->getMessage() : 'Internal Server Error']
				];
			}

			$this->_helper->json($response);
		}
		else
		{
			$code = $this->_request->getParam('q');

			if (!v::optional(v::stringType())->validate($code))
			{
				throw new RuntimeException('Incorrect code value: ' .
					var_export($code, true));
			}

			$type = $this->_request->getParam('regType');

			if (!v::optional(v::stringType())->validate($type))
			{
				throw new RuntimeException('Incorrect type value: ' .
					var_export($type, true));
			}

			$email = $this->_request->getParam('Email_id');

			if (!v::optional(v::email())->validate($email))
			{
				throw new RuntimeException('Incorrect email address value: ' .
					var_export($email, true));
			}

			if ($code == null || $type == null)
			{
				$this->_redirect('/');
			}

			$invite = $inviteForm->findByCode($code);

			if ($invite)
			{
				$this->view->regEmail = $invite->receiver_email ?:
					$invite->self_email;
			}
		}

		$this->view->email = $email;
		$this->view->layout()->setLayout('login');
	}
}
