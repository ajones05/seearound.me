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

						$login = (new Application_Model_Loginstatus)->save($user);
						$user->updateInviteCount();

						$auth = Zend_Auth::getInstance();
						$auth->getStorage()->write([
							'user_id' => $user->id,
							'login_id' => $login->id
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
						$confirm = $confirmModel->save([
							'user_id' => $user->id,
							'type_id' => $confirmModel::$type['registration']
						]);

						My_Email::send(
							$user->Email_id,
							'SeeAround.me Registration',
							[
								'template' => 'registration',
								'assign' => ['user' => $user, 'code' => $confirm->code],
								'settings' => $settings
							]
						);

						$login = (new Application_Model_Loginstatus)->save($user);

						Zend_Auth::getInstance()->getStorage()->write(array(
							"user_id" => $user->id,
							"login_id" => $login->id
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
		$login = (new Application_Model_Loginstatus)->save($user);
		$user->updateInviteCount();

		Zend_Auth::getInstance()->getStorage()->write(array(
			'user_id' => $user->id,
			'login_id' => $login->id
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

			$confirmModel = new Application_Model_UserConfirm;
			$confirmModel->deleteUserCode($user, $confirmModel::$type['registration']);
			$confirm = $confirmModel->save([
				'user_id' => $user->id,
				'type_id' => $confirmModel::$type['registration']
			]);

			$settings = Application_Model_Setting::getInstance();

			My_Email::send(
				[$user->Name => $user->Email_id],
				'Re-send activation link',
				[
					'template' => 'user-resend',
					'assign' => ['user' => $user, 'code' => $confirm->code],
					'settings' => $settings
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
					$confirm = $confirmModel->save([
						'user_id' => $user->id,
						'type_id' => $confirmModel::$type['password']
					]);

					$settings = Application_Model_Setting::getInstance();

					My_Email::send(
						$user->Email_id,
						'Forgot Password',
						[
							'template' => 'forgot-password',
							'assign' => ['confirm' => $confirm],
							'settings' => $settings
						]
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
				$user->password = $userModel->encryptPassword($data['password']);
				$user->save();

				$confirmModel = new Application_Model_UserConfirm;
				$confirmModel->deleteUserCode($user, $confirmModel::$type['password']);

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
