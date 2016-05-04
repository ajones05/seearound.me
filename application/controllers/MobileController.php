<?php
use Respect\Validation\Validator as v;

/**
 * Mobile API class.
 */
class MobileController extends Zend_Controller_Action
{
	/**
	 * @var	Zend_Log
	 */
	protected $_logger;

	/**
	 * Initialize object
	 *
	 * @return void
	 */
	public function init()
	{
		$log_path = ROOT_PATH . '/log';
		is_dir($log_path) || mkdir($log_path, 0700);
		$writer = new Zend_Log_Writer_Stream($log_path . '/mobile_api_' . date('Y-m-d') . '.log');
		$this->_logger = new Zend_Log($writer);

		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
	}

	/**
	 * Authenticate user action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		try
		{
			$email = $this->_request->getPost('email');

			if (!v::email()->validate($email))
			{
				throw new RuntimeException('Incorrect email address value: ' .
					var_export($email, true));
			}

			$password = $this->_request->getPost('password');

			if (!v::stringType()->validate($password))
			{
				throw new RuntimeException('Incorrect password value: ' .
					var_export($password, true));
			}

			$userModel = new Application_Model_User;
			$user = $userModel->findByEmail($email);

			// TODO: password_verify($password, $user->password_hash)
			if (!$user || $user->Password !== hash('sha256', $password))
			{
				throw new RuntimeException('Incorrect user email or password');
			}

			if (!$user->password_hash)
			{
				$userModel->update(['password_hash' => password_hash($password, PASSWORD_BCRYPT)],
					'id=' . $user->id);
			}

			if ($user->Status != 'active')
			{
				throw new RuntimeException('User is not active', -1);
			}

			$user->updateToken();

			$login_id = (new Application_Model_Loginstatus)->insert([
				'user_id' => $user->id,
				'login_time' => new Zend_Db_Expr('NOW()'),
				'visit_time' => new Zend_Db_Expr('NOW()'),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			]);

			if (date('N') == 1)
			{
				$user->updateInviteCount();
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'AUTHENTICATED',
				'result' => [
					'id' => $user->id,
					'karma' => round($userModel->getKarma($user->id)['karma'], 4),
					'Name' => $user->Name,
					'Email_id' => $user->Email_id,
					'Birth_date' => $user->Birth_date,
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($user->getThumb('320x320')['path']),
					'Status' => $user->Status,
					'Token' => $user->Token,
					'address' => Application_Model_Address::format($user),
					'latitude' => $user->latitude,
					'longitude' => $user->longitude,
					'Activities' => $user->activities(),
					'Gender' => $user->gender(),
					'login_id' => $login_id
				]
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Authenticate user with facebook action.
	 *
	 * @return void
	 */
	public function fbLoginAction()
	{
		try
		{
			$token = $this->_request->getPost('token');

			if (trim($token) === '')
			{
				throw new RuntimeException('Facebook access token cannot be blank', -1);
			}

			$config = Zend_Registry::get('config_global');
			Facebook\FacebookSession::setDefaultApplication($config->facebook->app->id, $config->facebook->app->secret);

			$session = new Facebook\FacebookSession($token);
			$user = (new Application_Model_User)->facebookAuthentication($session);

			$login_id = (new Application_Model_Loginstatus)->insert([
				'user_id' => $user->id,
				'login_time' => new Zend_Db_Expr('NOW()'),
				'visit_time' => new Zend_Db_Expr('NOW()'),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			]);

			if (date('N') == 1)
			{
				$user->updateInviteCount();
			}

			$response = [
				'status' => 'SUCCESS',
				'result' => [
					'id' => $user->id,
					'Name' => $user->Name,
					'Email_id' => $user->Email_id,
					'Birth_date' => $user->Birth_date,
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($user->getThumb('320x320')['path']),
					'Status' => $user->Status,
					'Token' => $user->Token,
					'address' => Application_Model_Address::format($user),
					'latitude' => $user->latitude,
					'longitude' => $user->longitude,
					'Activities' => $user->activities(),
					'Gender' => $user->gender(),
					'login_id' => $login_id
				]
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ||
					$e instanceof Facebook\FacebookAuthorizationException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Register a user action.
	 *
	 * @return void
	 */
	public function registrationAction()
	{
		try
		{
			$form = new Application_Form_Registration;
			$data = $this->_request->getPost();

			if (!$form->isValid($data))
			{
				$this->_formValidateException($form);
			}

			$addressForm = new Application_Form_Address;

			if (!$addressForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $addressForm->getErrorMessages()));
			}

			$user = (new Application_Model_User)->register(
				array_merge($data,['Status' => 'active'])
			);

			$user->updateToken();

			My_Email::send(
				$user->Email_id,
				'seearound.me new Registration',
				array('template' => 'ws-registration')
			);

			$login_id = (new Application_Model_Loginstatus)->insert([
				'user_id' => $user->id,
				'login_time' => new Zend_Db_Expr('NOW()'),
				'visit_time' => new Zend_Db_Expr('NOW()'),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			]);

			$response = array(
				'status' => 'SUCCESS',
				'login_id' => $login_id
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Reset password api gateway.
	 *
	 * @return void
	 */
	public function resetPasswordAction()
	{
		try
		{
			$email = $this->_request->getPost('email');

			if (!v::email()->validate($email))
			{
				throw new RuntimeException('Incorrect email value: ' .
					var_export($email, true));
			}

			$userModel = new Application_Model_User;
			$user = $userModel->findByEmail($email);

			if (!$user)
			{
				throw new RuntimeException('No account found with that email address: ' .
					var_export($email, true));
			}

			if ($user->Status !== 'active')
			{
				throw new RuntimeException('This account is not active');
			}

			$confirmModel = new Application_Model_UserConfirm;
			$confirm = $confirmModel->save([
				'user_id' => $user->id,
				'type_id' => $confirmModel::$type['forgot_password']
			]);

			My_Email::send(
				$email,
				'Forgot Password',
				['template' => 'forgot-password', 'assign' => ['confirm' => $confirm]]
			);

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);
		$this->_helper->json($response);
	}

	/**
	 * Friends list action.
	 *
	 * @return void
	 */
	public function myfriendlistAction() 
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'My Friend list rendered successfully'
			];

			$friends = (new Application_Model_Friends)->findAllByUserId($user->id, 100, $start);

			if (count($friends))
			{
				foreach ($friends as $friend)
				{
					$friendUserId = $friend->reciever_id == $user->id ? $friend->sender_id : $friend->reciever_id;
					// TODO: merge
					$friendUser = Application_Model_User::findById($friendUserId);

					$response['result'][] = My_ArrayHelper::filter([
						'id' => $friendUser->id,
						'Name' => $friendUser->Name,
						'Email_id' => $friendUser->Email_id,
						'Profile_image' => $this->view->serverUrl() .
							$this->view->baseUrl($friendUser->getThumb('320x320')['path']),
						'Birth_date' => $friendUser->Birth_date,
						'Gender' => $friendUser->gender(),
						'Activities' => $friendUser->activities()
					]);
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Follow user action.
	 *
	 * @return void
	 */
	public function followAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID');
			}

			$receiver_id = $this->_request->getPost('receiver_id');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver user ID value: ' .
					var_export($receiver_id, true));
			}

			if (!Application_Model_User::checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect follow user ID');
			}

			$model = new Application_Model_Friends;

			if ($model->isFriend($user, $receiver))
			{
				throw new RuntimeException('User already in friend list');
			}

			$model->createRow([
				'sender_id' => $user->id,
				'reciever_id' => $receiver->id,
				'status' => $model->status['confirmed'],
				'source' => 'herespy'
			])->updateStatus($user);

			My_Email::send($receiver->Email_id, 'New follower', [
				'template' => 'friend-invitation',
				'assign' => ['name' => $user->Name]
			]);

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Unfollow user action.
	 *
	 * @return void
	 */
	public function unfollowAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID');
			}

			$receiver_id = $this->_request->getPost('receiver_id');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver user ID value: ' .
					var_export($receiver_id, true));
			}

			if (!Application_Model_User::checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect follow user ID');
			}

			$model = new Application_Model_Friends;
			$friend = $model->isFriend($user, $receiver);

			if (!$friend)
			{
				throw new RuntimeException('User not found in friend list');
			}

			$friend->status = $model->status['rejected'];
			$friend->updateStatus($user);

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Profile details action.
	 *
	 * @return void
	 */
    public function getotheruserprofileAction()
    {
		try
		{
			$userModel = new Application_Model_User;
			$other_user_id = $this->_request->getPost('other_user_id');

			if (!v::intVal()->validate($other_user_id))
			{
				throw new RuntimeException('Incorrect other user ID value: ' .
					var_export($other_user_id, true));
			}

			if (!$userModel->checkId($other_user_id, $profile))
			{
				throw new RuntimeException('Incorrect other user ID: ' .
					var_export($other_user_id, true));
			}

			$response = [
				'status' => 'SUCCESS',
				'result' => My_ArrayHelper::filter([
					'id' => $profile->id,
					'karma' => round($userModel->getKarma($profile->id)['karma'], 4),
					'Name' => $profile->Name,
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($profile->getThumb('320x320')['path']),
					'Email_id' => $profile->Email_id,
					'Gender' => $profile->gender(),
					'Activities' => $profile->activities(),
					'Birth_date' => $profile->Birth_date
				])
			];

			$user_id = $this->_request->getPost('user_id');

			if (!v::optional(v::intVal())->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if ($user_id != null)
			{
				if ($user_id == $other_user_id)
				{
					throw new RuntimeException('Other user ID cannot be the same as your ID');
				}

				if (!Application_Model_User::checkId($user_id, $user))
				{
					throw new RuntimeException('Incorrect user ID: ' .
						var_export($user_id, true));
				}

				$response['friends'] = (new Application_Model_Friends)->isFriend($user, $profile) ? 1 : 0;
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Send message action.
	 *
	 * @return void
	 */
	public function sendmessageAction()
	{
		try
		{
			$sender_id = $this->_request->getPost('sender_id');

			if (!v::intVal()->validate($sender_id))
			{
				throw new RuntimeException('Incorrect sender ID value: ' .
					var_export($sender_id, true));
			}

			if (!Application_Model_User::checkId($sender_id, $user))
			{
				throw new RuntimeException('Incorrect sender ID: ' .
					var_export($sender_id, true));
			}

			$receiver_id = $this->_request->getPost('reciever_id');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver ID value: ' .
					var_export($receiver_id, true));
			}

			if (!Application_Model_User::checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect receiver ID: ' .
					var_export($receiver_id, true));
			}

			$body = $this->_request->getPost('message');

			if (!v::stringType()->length(1, 65535)->validate($body))
			{
				throw new RuntimeException('Incorrect body value: ' .
					var_export($body, true));
			}

			$conversation_id = $this->_request->getPost('conversation_id');

			if (!v::optional(v::intVal())->validate($conversation_id))
			{
				throw new RuntimeException('Incorrect conversation ID value: ' .
					var_export($conversation_id, true));
			}

			$conversationModel = new Application_Model_Conversation;

			if ($conversation_id)
			{
				if (!$conversationModel->checkId($conversation_id, $conversation))
				{
					throw new RuntimeException('Incorrect conversation ID: ' .
						var_export($conversation_id, true));
				}

				if ($conversation->from_id != $user->id && $conversation->to_id != $user->id ||
					$conversation->from_id != $receiver->id && $conversation->to_id != $receiver->id)
				{
					throw new RuntimeException('You are not authorized to access this action');
				}
			}
			else
			{
				$subject = $this->_request->getPost('subject');

				if (!v::stringType()->length(1, 250)->validate($subject))
				{
					throw new RuntimeException('Incorrect subject value: ' .
						var_export($subject, true));
				}

				$conversation = $conversationModel->save([
					'from_id' => $user->id,
					'to_id' => $receiver->id,
					'subject' => $subject
				]);
			}

			$message = (new Application_Model_ConversationMessage)->save([
				'conversation_id' => $conversation->id,
				'from_id' => $user->id,
				'to_id' => $receiver->id,
				'body' => $body,
				'is_first' => !$conversation_id ? 1 : 0
			]);

			My_Email::send(
				[$receiver->Name => $receiver->Email_id],
				$conversation->subject,
				[
					'template' => 'message-notification',
					'assign' => [
						'sender' => $user,
						'receiver' => $receiver,
						'subject' => $conversation->subject,
						'message' => $message->body
					]
				]
			);

			$response = [
				'status' => "SUCCESS",
				'message' => "Message Send Successfully",
				'result' => [
					'id' => $conversation->id,
					'created' => (new DateTime($conversation->created_at))
						->setTimezone($user->getTimezone())
						->format(My_Time::SQL)
				]
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * List of user unread messages action.
	 *
	 * @return void
	 */
	public function unreadmessagesAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$model = new Application_Model_Conversation;
			$query = $model->select()
				->setIntegrityCheck(false)
				->from(['c' => 'conversation'], [
					'c.id',
					'c.subject',
					'cm1.body',
					'cm1.created_at',
					'user_id' => 'u.id',
					'user_name' => 'u.Name',
					'user_email' => 'u.Email_id'
				])
				->where('c.to_id=?', $user->id)
				->joinLeft(['cm1' => 'conversation_message'], '(cm1.conversation_id=c.id AND ' .
					'cm1.is_first=1)', '')
				->joinLeft(['cm3' => 'conversation_message'], '(cm3.conversation_id=c.id AND ' .
					'cm3.is_read=0 AND cm3.to_id=' . $user->id . ')', '')
				->where('cm3.id IS NOT NULL')
				->joinLeft(['u' => 'user_data'], 'u.id=c.from_id', '')
				->group('c.id')
				->order('c.created_at DESC')
				->limit(100, $start);

			My_Query::setThumbsQuery($query, [[320, 320]], 'u');
			$messages = $model->fetchAll($query);

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Message list Send Successfully'
			];

			if (count($messages))
			{
				$userTimezone = $user->getTimezone();
				foreach ($messages as $message)
				{
					$thumb = My_Query::getThumb($message, '320x320', 'u', true);
					$response['result'][] = [
						'id' => $message->id,
						'sender_id' => $message->user_id,
						'subject' => $message->subject,
						'message' => $message->body,
						'created' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'reciever_read' => 0,
						'Name' => $message->user_name,
						'Email_id' => $message->user_email,
						'Profile_image' => $this->view->serverUrl() .
							$this->view->baseUrl($thumb['path'])
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Retrieve message conversation action.
	 * 
	 * @return	void
	 */
	public function messageConversationAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			$userModel = new Application_Model_User;

			if (!$userModel->checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$other_user_id = $this->_request->getPost('other_user_id');

			if (!v::optional(v::intVal())->validate($other_user_id))
			{
				throw new RuntimeException('Incorrect other user ID value: ' .
					var_export($other_user_id, true));
			}

			if ($other_user_id && !$userModel->checkId($other_user_id, $other_user))
			{
				throw new RuntimeException('Incorrect other user ID: ' .
					var_export($other_user_id, true));
			}

			$response = ['status' => 'SUCCESS'];

			$model = new Application_Model_Conversation;
			$query = $model->select()->setIntegrityCheck(false)
				->from(['c' => 'conversation'], [
					'c.id',
					'c.subject',
					'cm1.body',
					'cm1.created_at',
					'user_id' => 'u.id',
					'user_name' => 'u.Name',
					'user_email' => 'u.Email_id',
					'is_read' => 'IFNULL(cm3.is_read,1)'
				])
				->joinLeft(['cm1' => 'conversation_message'], '(cm1.conversation_id=c.id AND ' .
					'cm1.is_first=1)', '')
				->joinLeft(['cm3' => 'conversation_message'], '(cm3.conversation_id=c.id AND ' .
					'cm3.is_read=0 AND cm3.to_id=' . $user->id . ')', '')
				->joinLeft(['u' => 'user_data'], 'u.id=c.from_id', '')
				->group('c.id')
				->order('c.created_at DESC')
				->limit(100, $start);

			if ($other_user_id)
			{
				$query->where('(c.to_id=?',  $user->id)
					->where('c.from_id=?)', $other_user->id)
					->orWhere('(c.to_id=?',  $other_user->id)
					->where('c.from_id=?)', $user->id);
				$response['message'] = 'Inbox Message between two user rendered Successfully';
			}
			else
			{
				$query->where('(c.to_id=?',  $user->id)
					->orWhere('c.from_id=?)', $user->id);
				$response['message'] = 'Message list Send Successfully';
			}

			My_Query::setThumbsQuery($query, [[320, 320]], 'u');
			$messages = $model->fetchAll($query);

			if (count($messages))
			{
				$userTimezone = $user->getTimezone();
				foreach ($messages as $message)
				{
					$thumb = My_Query::getThumb($message, '320x320', 'u', true);
					$response['result'][] = [
						'id' => $message->id,
						'sender_id' => $message->user_id,
						'subject' => $message->subject,
						'message' => $message->body,
						'created' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'reciever_read' => $message->is_read,
						'Name' => $message->user_name,
						'Email_id' => $message->user_email,
						'Profile_image' => $this->view->serverUrl() .
							$this->view->baseUrl($thumb['path'])
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Conversation messages list action.
	 * 
	 * @return	void
	 */
	public function conversationMessageAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect ID value: ' .
					var_export($id, true));
			}

			$conversationModel = new Application_Model_Conversation;

			if (!$conversationModel->checkId($id, $conversation))
			{
				throw new RuntimeException('Incorrect conversation ID', -1);
			}

			if ($user->id != $conversation->to_id && $user->id != $conversation->from_id)
			{
				throw new RuntimeException('You have no permissions to access this action');
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$messageModel = new Application_Model_ConversationMessage;
			$query = $messageModel->select()
				->setIntegrityCheck(false)
				->from(['cm' => 'conversation_message'], [
					'cm.id',
					'cm.to_id',
					'cm.body',
					'cm.is_read',
					'cm.created_at',
					'sender_id' => 'su.id',
					'sender_name' => 'su.Name',
					'sender_email' => 'su.Email_id',
					'receiver_id' => 'ru.id',
					'receiver_name' => 'ru.Name',
					'receiver_email' => 'ru.Email_id'
				])
				->where('cm.conversation_id=?', $conversation->id)
				->where('cm.is_first<>1')
				->joinLeft(['su' => 'user_data'], 'su.id=cm.from_id', '')
				->joinLeft(['ru' => 'user_data'], 'ru.id=cm.to_id', '')
				->order('cm.created_at DESC')
				->limit(10, $start);

			My_Query::setThumbsQuery($query, [[320, 320]], 'su');
			My_Query::setThumbsQuery($query, [[320, 320]], 'ru');
			$messages = $messageModel->fetchAll($query);

			$updateCondition = [];

			if ($conversation->to_id == $user->id)
			{
				$updateCondition[] = '(conversation_id=' . $conversation->id .
					' AND is_first=1)';
			}

			$response = ['status' => 'SUCCESS'];

			if ($messages->count())
			{
				$userTimezone = $user->getTimezone();
				foreach ($messages as $message)
				{
					$senderThumb = My_Query::getThumb($message, '320x320', 'su', true);
					$receiverThumb = My_Query::getThumb($message, '320x320', 'ru', true);
					$response['result'][] = [
						'id' => $message->id,
						'body' => $message->body,
						'created_at' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'sender_id' => $message->sender_id,
						'sender_name' => $message->sender_name,
						'sender_email' => $message->sender_email,
						'sender_image' =>  $this->view->serverUrl() .
							$this->view->baseUrl($senderThumb['path']),
						'receiver_id' => $message->receiver_id,
						'receiver_name' => $message->receiver_name,
						'receiver_email' => $message->receiver_email,
						'receiver_image' =>  $this->view->serverUrl() .
							$this->view->baseUrl($receiverThumb['path']),
						'is_read' => $message->is_read
					];

					if ($message->to_id == $user->id)
					{
						$updateCondition[] = 'id=' . $message->id;
					}
				}
			}

			if (count($updateCondition))
			{
				$messageModel->update(['is_read' => 1],
					implode(' OR ', $updateCondition));
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => true || $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Set notificatations status action.
	 * 
	 * @return	void
	 */
	public function viewedAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$post_id = $this->_request->getPost('post_id');

			if (!v::stringType()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			$ids = explode(',', $post_id);

			if (!count($ids))
			{
				throw new RuntimeException('Post ID cannot be blank');
			}

			$conversationIds = array();
			$conversationModel = new Application_Model_Conversation;

			foreach ($ids as $id)
			{
				$conversation = $conversationModel->findByID($id);

				if (!$conversation)
				{
					throw new RuntimeException('Incorrect conversation ID: ' .
						var_export($id, true), -1);
				}

				switch ($user->id)
				{
					case $conversation->from_id:
						break;
					case $conversation->to_id:
						$conversationIds[] = $conversation->id;
						break;
					default:
						throw new RuntimeException('You are not authorized to access this action', -1);
				}
			}

			if (count($conversationIds))
			{
				$messageModel = new Application_Model_ConversationMessage;
				$messageModel->update(array('is_read' => 1),
					'is_first=1 AND conversation_id IN (' . implode(',', $conversationIds) . ')');
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Read Inbox Message Successfully'
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Messages list action.
	 * 
	 * @return	void
	 */
	public function messagesAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			$userModel = new Application_Model_User;

			if (!$userModel->checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$other_user_id = $this->_request->getPost('other_user_id');

			if (!v::intVal()->validate($other_user_id))
			{
				throw new RuntimeException('Incorrect other user ID value: ' .
					var_export($other_user_id, true));
			}

			if (!$userModel->checkId($other_user_id, $other_user))
			{
				throw new RuntimeException('Incorrect other user ID: ' .
					var_export($other_user_id, true));
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$messageModel = new Application_Model_ConversationMessage;
			$query = $messageModel->select()
				->setIntegrityCheck(false)
				->from(['cm' => 'conversation_message'], [
					'cm.id',
					'c.subject',
					'cm.body',
					'cm.is_read',
					'cm.created_at',
					'sender_id' => 'su.id',
					'sender_name' => 'su.Name',
					'sender_email' => 'su.Email_id',
					'receiver_id' => 'ru.id',
					'receiver_name' => 'ru.Name',
					'receiver_email' => 'ru.Email_id'
				])
				->joinLeft(['c' => 'conversation'], 'c.id=cm.conversation_id', '')
				->where('(c.to_id=?',  $user->id)
				->where('c.from_id=?)', $other_user->id)
				->orWhere('(c.to_id=?',  $other_user->id)
				->where('c.from_id=?)', $user->id)
				->joinLeft(['su' => 'user_data'], 'su.id=cm.from_id', '')
				->joinLeft(['ru' => 'user_data'], 'ru.id=cm.to_id', '')
				->group('cm.id')
				->order('cm.created_at DESC')
				->limit(10, $start);

			My_Query::setThumbsQuery($query, [[320, 320]], 'su');
			My_Query::setThumbsQuery($query, [[320, 320]], 'ru');
			$messages = $messageModel->fetchAll($query);

			$response = ['status' => 'SUCCESS'];

			if ($messages->count())
			{
				$userTimezone = $user->getTimezone();
				foreach ($messages as $message)
				{
					$senderThumb = My_Query::getThumb($message, '320x320', 'su', true);
					$receiverThumb = My_Query::getThumb($message, '320x320', 'ru', true);
					$response['result'][] = [
						'id' => $message->id,
						'subject' => $message->subject,
						'body' => $message->body,
						'is_read' => $message->is_read,
						'created_at' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'sender_id' => $message->sender_id,
						'sender_name' => $message->sender_name,
						'sender_email' => $message->sender_email,
						'sender_image' => $this->view->serverUrl() .
							$this->view->baseUrl($senderThumb['path']),
						'receiver_id' => $message->receiver_id,
						'receiver_name' => $message->receiver_name,
						'receiver_email' => $message->receiver_email,
						'receiver_image' => $this->view->serverUrl() .
							$this->view->baseUrl($receiverThumb['path']),
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Post details action.
	 *
	 * @return	void
	 */
	public function postAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}

			$post_id = $this->_request->getPost('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			if (!Application_Model_News::checkId($post_id, $post, ['link'=>true]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			$userLike = (new Application_Model_Voting)
				->findVote($post->id, $user->id);
			$ownerThumb = My_Query::getThumb($post, '320x320', 'owner', true);

			$response = [
				'status' => 'SUCCESS',
				'post' => [
					'id' => $post->id,
					'user_id' => $post->user_id,
					'news' => $post->news,
					'created_date' => My_Time::time_ago($post->created_date),
					'updated_date' => (new DateTime($post->updated_date))
						->setTimezone($user->getTimezone())
						->format(My_Time::SQL),
					'latitude' => $post->latitude,
					'longitude' => $post->longitude,
					'Address' => Application_Model_Address::format($post) ?: $post->address,
					'comment_count' => $post->comment,
					'vote' => $post->vote,
					'isLikedByUser' => $userLike !== null ? $userLike->vote : '0',
					'Name' => $post->owner_name,
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($ownerThumb['path'])
				]
			];

			if ($post->image_id)
			{
				$thumb = My_Query::getThumb($post, '448x320', 'news');
				$response['post']['images'] = $this->view->serverUrl() .
					$this->view->baseUrl($thumb['path']);
			}

			if ($post->link_id != null)
			{
				$response['post']['link_url'] = $post->link_link;

				if (trim($post->link_title) !== '')
				{
					$response['post']['link_title'] = $post->link_title;
				}

				if (trim($post->link_description) !== '')
				{
					$response['post']['link_description'] = $post->link_description;
				}

				if (trim($post->link_author) !== '')
				{
					$response['post']['link_author'] = $post->link_author;
				}

				if ($post->link_image_id != null)
				{
					$thumb = My_Query::getThumb($post, '448x320', 'link');
					$response['post']['link_image'] = $this->view->serverUrl() .
						$this->view->baseUrl($thumb['path']);
				}
			}
        }
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Add news action.
	 * 
	 * @return	void
	 */
	public function addimobinewsAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$data = $this->_request->getPost();
			$postForm = new Application_Form_News;
			$postForm->setScenario('new');

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $postForm->getErrorMessages()));
			}

			$addressForm = new Application_Form_Address;

			if (!$addressForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $addressForm->getErrorMessages()));
			}

			$address = (new Application_Model_Address)
				->createRow($addressForm->getValues());
			$address->save();

			$model = new Application_Model_News;
			$post = $model->save($postForm->getValues() +
				['user_id' => $user->id, 'address_id' => $address->id]);

			$response = [
				'status' => 'SUCCESS',
				'userid' => $user->id,
				'message' => $post->news
			];

			// TODO: refactoring
			$postLink = $post->findParentRow('Application_Model_NewsLink');

			if ($postLink)
			{
				$response += ['link_url' => $postLink->link];

				if (trim($postLink->title) !== '')
				{
					$response += ['link_title' => $postLink->title];
				}

				if (trim($postLink->description) !== '')
				{
					$response += ['link_description' => $postLink->description];
				}

				if (trim($postLink->author) !== '')
				{
					$response += ['link_author' => $postLink->author];
				}

				if ($postLink->image_id != null)
				{
					$image = $postLink->findParentRow('Application_Model_Image');
					$response += ['link_image' => $this->view->serverUrl() .
						$this->view->baseUrl($image->findThumb([448, 320])->path)];
				}
			}

			if ($post->image_id != null)
			{
				$image = $post->findDependentRowset('Application_Model_Image')->current();
				$response += ['image' => $this->view->serverUrl() .
					$this->view->baseUrl($image->findThumb([448, 320])->path)];
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Before save post action.
	 *
	 * @return void
	 */
	public function beforeSavePostAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}

			$data = $this->_request->getPost();

			// TODO: change post body field name
			if (isset($data['body']))
			{
				$data['news'] = $data['body'];
			}

			$postForm = new Application_Form_News;
			$postForm->setScenario('before-save');

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $postForm->getErrorMessages()));
			}

			$linkModel = new Application_Model_NewsLink;
			$linkExist = null;

			if (preg_match_all('/' . My_CommonUtils::$link_regex . '/', $data['body'], $linkMatches))
			{
				foreach ($linkMatches[0] as $path)
				{
					$linkExist = $linkModel->findByLinkTrim($linkModel->trimLink($link));

					if ($linkExist != null)
					{
						break;
					}
				}
			}

			$response = ['status' => 'SUCCESS'];

			if ($linkExist != null)
			{
				$response['link_post_id'] = $linkExist->news_id;
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);
		$this->_helper->json($response);
	}

	/**
	 * Save post action.
	 *
	 * @return	void
	 */
	public function savePostAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}

			$post_id = $this->_request->getPost('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			$model = new Application_Model_News;

			if (!$model->checkId($post_id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			if ($user->id != $post->user_id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$data = $this->_request->getPost();

			// TODO: change post body field name
			if (isset($data['body']))
			{
				$data['news'] = $data['body'];
			}

			$postForm = new Application_Form_News;
			$postForm->setScenario('mobile-save');

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $postForm->getErrorMessages()));
			}

			$addressForm = new Application_Form_Address;

			if (!$addressForm->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $addressForm->getErrorMessages()));
			}

			$post = $model->save($postForm->getValues(), $post);

			$address = $post->findParentRow('Application_Model_Address');
			$address->setFromArray(['address'=>null]+$data);
			$address->save();

			// TODO: refactoring
			$post = $model->findById($post->id, ['link'=>true]);

			$response = [
				'status' => 'SUCCESS',
				'post' => [
					'body' => $post->news,
					'latitude' => $post->latitude,
					'longitude' => $post->longitude,
				] + My_ArrayHelper::filter([
					'address' => Application_Model_Address::format($post) ?: $post->address,
					'street_name' => $post->street_name,
					'street_number' => $post->street_number,
					'city' => $post->city,
					'state' => $post->state,
					'country' => $post->country,
					'zip' => $post->zip
				])
			];

			if ($post->image_id != null)
			{
				$image = $post->findDependentRowset('Application_Model_Image')->current();
				$response['post']['image'] = $this->view->serverUrl() .
						$this->view->baseUrl($image->path);
			}

			if ($post->link_id != null)
			{
				$response['post']['link_url'] = $post->link_link;

				if (trim($post->link_title) !== '')
				{
					$response['post']['link_title'] = $post->link_title;
				}

				if (trim($post->link_description) !== '')
				{
					$response['post']['link_description'] = $post->link_description;
				}

				if (trim($post->link_author) !== '')
				{
					$response['post']['link_author'] = $post->link_author;
				}

				if ($post->link_image_id != null)
				{
					$thumb = My_Query::getThumb($post, '448x320', 'link');
					$response['post']['link_image'] = $this->view->serverUrl() .
						$this->view->baseUrl($thumb['path']);
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Delete post action.
	 *
	 * @return	void
	 */
	public function deletePostAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}

			$post_id = $this->_request->getPost('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			$model = new Application_Model_News;

			if (!$model->checkId($post_id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			if ($user->id != $post->user_id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$post->isdeleted = 1;
			$post->save();

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);
		$this->_helper->json($response);
	}

	/**
	 * Edit profile action.
	 * 
	 * @return	void
	 */
    public function editProfileAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			$userModel = new Application_Model_User;

			if (!$userModel->checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}

			$form = new Application_Form_MobileProfile;

			if (!$form->isValid($this->_request->getPost()))
			{
				$this->_formValidateException($form);
			}

			$data = $form->getValues();

			$userModel->getDefaultAdapter()->beginTransaction();

			try
			{
				$user_data = array(
					'Name' => $data['name'],
					'Birth_date' => trim($data['birth_date']) !== '' ?
						(new DateTime($data['birth_date']))->format('Y-m-d') : null,
					// 'Email_id' => $data['email']
				);

				if (trim(My_ArrayHelper::getProp($data, 'image')) !== '')
				{
					if ($user->image_id)
					{
						$user->findDependentRowset('Application_Model_Image')
							->current()->deleteImage();
					}

					$image = (new Application_Model_Image)->save('www/upload/' . $data['image']);
					$user_data['image_id'] = $image->id;

					$thumb26x26 = 'thumb26x26/' . $data['image'];
					$thumb55x55 = 'thumb55x55/' . $data['image'];
					$thumb320x320 = 'uploads/' . $data['image'];

					My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path, [
						[26, 26, ROOT_PATH_WEB . '/' . $thumb26x26, 2],
						[55, 55, ROOT_PATH_WEB . '/' . $thumb55x55, 2],
						[320, 320, ROOT_PATH_WEB . '/' . $thumb320x320]
					]);

					$thumbModel = new Application_Model_ImageThumb;
					$thumbModel->save($thumb26x26, $image, [26, 26]);
					$thumbModel->save($thumb55x55, $image, [55, 55]);
					$thumb = $thumbModel->save($thumb320x320, $image, [320, 320]);
					$profileImage = $thumb->path;
				}
				else
				{
					$profileImage = $user->getThumb('320x320')['path'];
				}

				$userModel->update($user_data, 'id=' . $user->id);

				$profile = $user->findDependentRowset('Application_Model_UserProfile')->current();

				if (!$profile)
				{
					$profile = (new Application_Model_UserProfile)->createRow(['user_id' => $user->id]);
				}

				$profile->public_profile = $data['public_profile'];
				$profile->Activities = $data['activities'];
				$profile->Gender = $data['gender'];
				$profile->save();

				$userModel->getDefaultAdapter()->commit();
			}
			catch (Exception $e)
			{
				$userModel->getDefaultAdapter()->rollBack();

				throw $e;
			}

			$userAddress = $user->findDependentRowset('Application_Model_Address')->current();

			$response = [
				'status' => 'SUCCESS',
				'message' => 'User profile has been updated successfully',
				'result' => My_ArrayHelper::filter([
					'user_id' => $user->id,
					'karma' => round($userModel->getKarma($user->id)['karma'], 4),
					'Name' => $data['name'],
					'Email_id' => $data['email'],
					'address' => Application_Model_Address::format($userAddress->toArray()),
					'latitude' => $userAddress->latitude,
					'longitude' => $userAddress->longitude,
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($profileImage),
					'Gender' => $data['gender'],
					'Activities' => $data['activities'],
					'Birth_date' => $data['birth_date']
				])
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
    }

	/**
	 * List neares news action.
	 * 
	 * @return	void
	 */
	public function requestNearestAction()
	{
		try
		{
			$user_id = $this->_request->getPost('userId');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radious', 1),
				'start' => $this->_request->getPost('start', 0)
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode("\n", $searchForm->getErrorMessages()));
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Nearest point data rendered successfully'
			];

			$result = (new Application_Model_News)
				->search($searchParameters + ['limit' => 15], $user, ['link'=>true]);

			if (count($result))
			{
				$userTimezone = $user->getTimezone();
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$userLike = $votingTable->findVote($row->id, $user->id);
					$ownerThumb = My_Query::getThumb($row, '320x320', 'owner', true);

					$data = [
						'id' => $row->id,
						'user_id' => $row->user_id,
						'news' => $row->news,
						'created_date' => My_Time::time_ago($row->created_date),
						'updated_date' => (new DateTime($row->updated_date))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'isdeleted' => $row->isdeleted,
						'isflag' => $row->isflag,
						'isblock' => $row->isblock,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'Address' => Application_Model_Address::format($row) ?: $row->address,
						'comment_count' => $row->comment,
						'vote' => $row->vote,
						'isLikedByUser' => $userLike !== null ? $userLike->vote : '0',
						'Name' => $row->owner_name,
						'Profile_image' => $this->view->serverUrl() .
							$this->view->baseUrl($ownerThumb['path'])
					];

					if ($row->image_id)
					{
						$thumb = My_Query::getThumb($row, '448x320', 'news');
						$data['images'] = $this->view->serverUrl() .
							$this->view->baseUrl($thumb['path']);
					}

					if ($row->link_id)
					{
						$data += ['link_url' => $row->link_link];

						if (trim($row->link_title) !== '')
						{
							$data += ['link_title' => $row->link_title];
						}

						if (trim($row->link_description) !== '')
						{
							$data += ['link_description' => $row->link_description];
						}

						if (trim($row->link_author) !== '')
						{
							$data += ['link_author' => $row->link_author];
						}

						if ($row->link_image_id != null)
						{
							$thumb = My_Query::getThumb($row, '448x320', 'link');
							$data += ['link_image' => $this->view->serverUrl() .
								$this->view->baseUrl($thumb['path'])];
						}
					}

					$response['result'][] = $data;
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * List user posts action.
	 *
	 * @return	void
	 */
	public function mypostsAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radious', 0.8),
				'keywords' => $this->_request->getPost('searchText'),
				'filter' => $this->_request->getPost('filter'),
				'start' => $this->_request->getPost('start', 0)
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode("\n", $searchForm->getErrorMessages()));
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Posts rendred successfully'
			];

			if ($searchParameters['filter'] == 1)
			{
				$response['interest'] = count($user->parseInterests());
			}

			$result = (new Application_Model_News)
				->search($searchParameters + ['limit' => 15], $user, ['link'=>true]);

			if (count($result))
			{
				$userTimezone = $user->getTimezone();
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$userLike = $votingTable->findVote($row->id, $user->id);
					$ownerThumb = My_Query::getThumb($row, '320x320', 'owner', true);

					$data = [
						'id' => $row->id,
						'user_id' => $row->user_id,
						'news' => $row->news,
						'created_date' => My_Time::time_ago($row->created_date),
						'updated_date' => (new DateTime($row->updated_date))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'isdeleted' => $row->isdeleted,
						'isflag' => $row->isflag,
						'isblock' => $row->isblock,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'Address' => Application_Model_Address::format($row) ?: $row->address,
						'comment_count' => $row->comment,
						'vote' => $row->vote,
						'isLikedByUser' => $userLike !== null ? $userLike->vote : '0',
						'Name' => $row->owner_name,
						'Profile_image' => $this->view->serverUrl() .
							$this->view->baseUrl($ownerThumb['path'])
					];

					if ($row->image_id)
					{
						$thumb = My_Query::getThumb($row, '448x320', 'news');
						$data['images'] = $this->view->serverUrl() .
							$this->view->baseUrl($thumb['path']);
					}

					if ($row->link_id)
					{
						$data += ['link_url' => $row->link_link];

						if (trim($row->link_title) !== '')
						{
							$data += ['link_title' => $row->link_title];
						}

						if (trim($row->link_description) !== '')
						{
							$data += ['link_description' => $row->link_description];
						}

						if (trim($row->link_author) !== '')
						{
							$data += ['link_author' => $row->link_author];
						}

						if ($row->link_image_id != null)
						{
							$thumb = My_Query::getThumb($row, '448x320', 'link');
							$data += ['link_image' => $this->view->serverUrl() .
								$this->view->baseUrl($thumb['path'])];
						}
					}

					$response['result'][] = $data;
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * List news comments action.
	 *
	 * @return	void
	 */
    public function getTotalCommentsAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$id = $this->_request->getPost('news_id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->min(0)->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Comments rendred successfully',
			];

			$comments = (new Application_Model_Comments)
				->findAllByNewsId($post->id, 10, $start);

			if (count($comments))
			{
				$userTimezone = $user->getTimezone();
				foreach ($comments as $comment)
				{
					// TODO: merge
					$owner = Application_Model_User::findById($comment->user_id);

					$response['result'][] = [
                        'id' => $comment->id,
                        'news_id' => $post->id,
                        'comment' => $comment->comment,
                        'user_name' => $owner->Name,
                        'user_id' => $owner->id,
                        'Profile_image' => $this->view->serverUrl() .
							$this->view->baseUrl($owner->getThumb('320x320')['path']),
						'commTime' => (new DateTime($comment->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
                        'totalComments' => $post->comment
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
    }

	/**
	 * Post news comment action.
	 *
	 * @return void
	 */
    public function postCommentAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$id = $this->_request->getPost('news_id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $news, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($id, true));
			}

			$form = new Application_Form_Comment;

			if (!$form->isValid($this->_request->getPost()))
			{
				$this->_formValidateException($form);
			}

			// TODO: refactoring
			$comment = (new Application_Model_Comments)->save($form, $news, $user);

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Comments Post Successfully',
				'result' => [
					'id' => $comment->id,
					'news_id' => $news->id,
					'comment' => $comment->comment,
					'user_name' => $user->Name,
					'user_id' => $user->id,
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($user->getThumb('320x320')['path']),
					'commTime' => (new DateTime($comment->created_at))
						->setTimezone($user->getTimezone())
						->format(My_Time::SQL),
					'totalComments' => $news->comment
				]
			];
		}
		catch (Exception $e)
		{
			$response =[
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
     }

	/**
	 * Delete post comment action.
	 *
	 * @return void
	 */
    public function deleteCommentAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$id = $this->_request->getPost('comment_id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect comment ID value: ' .
					var_export($id, true));
			}

			$model = new Application_Model_Comments;

			if (!$model->checkId($id, $comment, 0))
			{
				throw new RuntimeException('Incorrect comment ID: ' .
					var_export($id, true));
			}

			$post = $comment->findDependentRowset('Application_Model_News')->current();

			if ($post->isdeleted)
			{
				throw new RuntimeException('Incorrect comment ID: ' .
					var_export($id, true));
			}

			if ($user->id != $comment->user_id && $user->id != $post->user_id)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$model->deleteRow($comment, $post);

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response =[
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);
		$this->_helper->json($response);
     }

	/**
	 * Function to add like to a news.
	 * 
	 * @return void
	 */
	public function postLikeAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$id = $this->_request->getPost('news_id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID', -1);
			}

			$vote = $this->_request->getPost('vote');

			if (!v::intVal()->oneOf(v::equals(-1),v::equals(1))->validate($vote))
			{
				throw new RuntimeException('Incorrect vote value: ' .
					var_export($vote, true), -1);
			}

			$model = new Application_Model_Voting;

			if (!$model->canVote($user, $post))
			{
				throw new RuntimeException('You cannot vote this post', -1);
			}

			$userVote = $model->findVote($post->id, $user->id);

			if (!$user->is_admin && $userVote)
			{
				$userVote->updated_at = new Zend_Db_Expr('NOW()');
				$userVote->canceled = 1;
				$userVote->save();

				if ($post->vote == 0)
				{
					$lastVote = $model->findVote($post->id);

					if ($lastVote && $lastVote->vote)
					{
						$post->vote = $lastVote->vote;
						$post->save();
					}
				}
				else
				{
					$post->vote = max(0, $post->vote - $userVote->vote);
					$post->save();
				}
			}

			if ($user->is_admin || !$userVote || $userVote->vote != $vote)
			{
				$model->saveVotingData($vote, $user->id, $post);
				$activeVote = $vote;
			}
			else
			{
				$activeVote = 0;
			}

			$response = array(
				'success' => 'voted successfully',
				'vote' => $post->vote,
				'active' => $activeVote
			);
        }
		catch (Exception $e)
		{
			$response = array(
				'resonfailed' => 'Sorry unable to vote',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * List user notifications action.
	 *
	 * @return void
	 */
	public function notificationAction()
	{
		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!v::intVal()->validate($user_id))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($user_id, true));
			}

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' .
					var_export($user_id, true));
			}

			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->min(0)->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$response = ['status' => 'SUCCESS'];

			$maxDate = (new DateTime)->modify('-15 days')->setTime(0, 0)
				->format(My_Time::SQL);
			$db = Zend_Db_Table::getDefaultAdapter();

			$select1 = $db->select();
			$select1->from(['f' => 'friends'], [
				'f.id',
				'type' => new Zend_Db_Expr('"friend"'),
				'fl.created_at',
				'target_id' => new Zend_Db_Expr('NULL'),
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'is_read' => 'f.notify'
			]);
			$select1->where('f.reciever_id=? AND f.status=1', $user->id);
			$select1->where('f.notify=0 OR fl.created_at>=?', $maxDate);
			$select1->joinLeft(['fl' => 'friend_log'],
				'fl.friend_id=f.id AND fl.status_id=f.status', '');
			$select1->joinLeft(['u' => 'user_data'], 'u.id=fl.user_id', '');
			My_Query::setThumbsQuery($select1, [[320, 320]], 'u');

			$select2 = $db->select();
			$select2->from(['cm' => 'conversation_message'], [
				'cm.id',
				'type' => new Zend_Db_Expr('"message"'),
				'cm.created_at',
				'target_id' => new Zend_Db_Expr('NULL'),
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'is_read' => 'cm.is_read'
			]);
			$select2->where('cm.to_id=?', $user->id, $maxDate);
			$select2->where('cm.is_read=0 OR cm.created_at>?', $maxDate);
			$select2->joinLeft(['u' => 'user_data'], 'u.id=cm.from_id', '');
			My_Query::setThumbsQuery($select2, [[320, 320]], 'u');

			$select3 = $db->select();
			$select3->from(['n' => 'news'], [
				'v.id',
				'type' => new Zend_Db_Expr('"vote"'),
				'v.created_at',
				'target_id' => 'n.id',
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'is_read' => 'v.is_read'
			]);
			$select3->where('n.isdeleted=0 AND n.user_id=?', $user->id);
			$select3->joinLeft(['v' => 'votings'], 'v.news_id=n.id', '');
			$select3->where('v.canceled=0 AND v.user_id<>?', $user->id);
			$select3->where('v.is_read=0 OR v.created_at>?', $maxDate);
			$select3->joinLeft(['u' => 'user_data'], 'u.id=v.user_id', '');
			My_Query::setThumbsQuery($select3, [[320, 320]], 'u');

			$select4 = $db->select();
			$select4->from(['n' => 'news'], [
				'c.id',
				'type' => new Zend_Db_Expr('"comment"'),
				'c.created_at',
				'target_id' => 'n.id',
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'is_read' => 'c.is_read'
			]);
			$select4->where('n.isdeleted=0 AND n.user_id=?', $user->id);
			$select4->joinLeft(['c' => 'comments'], 'c.news_id=n.id', '');
			$select4->where('c.isdeleted=0 AND c.user_id<>?', $user->id);
			$select4->where('c.is_read=0 OR c.created_at>?', $maxDate);
			$select4->joinLeft(['u' => 'user_data'], 'u.id=c.user_id', '');
			My_Query::setThumbsQuery($select4, [[320, 320]], 'u');

			$select = $db->select()
				->union([$select1, $select2, $select3, $select4],
					Zend_Db_Select::SQL_UNION_ALL)
				->order('created_at DESC')
				->limit(10, $start);

			$result = $db->fetchAll($select);

			if (count($result))
			{
				$userTimezone = $user->getTimezone();
				$typeId = [];

				foreach ($result as $row)
				{
					$thumb = My_Query::getThumb($row, '320x320', 'u', true);
					$data = [
						'id' => $row['id'],
						'type' => $row['type'],
						'is_read' => $row['is_read'],
						'created_at' => (new DateTime($row['created_at']))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'user_id' => $row['user_id'],
						'user_name' => $row['user_name'],
						'user_image' => $this->view->serverUrl() .
							$this->view->baseUrl($thumb['path'])
					];

					switch ($row['type'])
					{
						case 'friend':
							$data['message'] = $row['user_name'] .
								' started following you';
							break;
						case 'message':
							$data['message'] = $row['user_name'] .
								' sent you a new message';
							break;
						case 'vote':
							$data['post_id'] = $row['target_id'];
							$data['message'] = $row['user_name'] .
								' liked your post';
							break;
						case 'comment':
							$data['post_id'] = $row['target_id'];
							$data['message'] = $row['user_name'] .
								' commented on your post';
							break;
					}

					$response['result'][] = $data;

					if (!$row['is_read'])
					{
						$typeId[$row['type']][] = $row['id'];
					}
				}

				if (!empty($typeId['friend']))
				{
					$db->update('friends', ['notify' => 1],
						'id IN(' . implode(',', $typeId['friend']) . ')');
				}

				if (!empty($typeId['message']))
				{
					$db->update('conversation_message', ['is_read' => 1],
						'id IN(' . implode(',', $typeId['message']) . ')');
				}

				if (!empty($typeId['vote']))
				{
					$db->update('votings', ['is_read' => 1],
						'id IN(' . implode(',', $typeId['vote']) . ')');
				}

				if (!empty($typeId['comment']))
				{
					$db->update('comments', ['is_read' => 1],
						'id IN(' . implode(',', $typeId['comment']) . ')');
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

	/**
	 * Writes to log rurrent request and response
	 *
	 * @param	string	$response
	 *
	 * @return	void
	 */
	protected function _logRequest($response)
	{
		$this->_logger->info($_SERVER['REQUEST_URI'] . "\n>> " . var_export($_REQUEST, true) . "\n<< " . var_export($response, true));
	}

	/**
	 * Returns form validate error exception
	 *
	 * @param	Zend_Form	$form
	 *
	 * @return	void
	 */
	protected function _formValidateException(Zend_Form $form)
	{
		$errors = array();

		foreach ($form->getMessages() as $field => $field_errors)
		{
			$_errors = array();

			foreach ($field_errors as $validator => $error)
			{
				$_errors[] = ltrim($error, '- ');
			}

			$errors[] = '"' . $field . '" - ' . implode(', ', $_errors);
		}

		throw new RuntimeException('Validate error: ' . implode(', ', $errors), -1);
	}
}
