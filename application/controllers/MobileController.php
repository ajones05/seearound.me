<?php
use Respect\Validation\Validator as v;

/**
 * Mobile API class.
 */
class MobileController extends Zend_Controller_Action
{
	/**
	 * @var array Contains site settings
	 */
	protected $settings;

	/**
	 * Initialize object
	 */
	public function init()
	{
		$this->settings = Application_Model_Setting::getInstance();

		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');

		if ($this->settings['api_enable'] == 0)
		{
			$this->_helper->json([
					'status' => 0,
					'message' => 'This site is down for maintenance'
			]);
		}
	}

	/**
	 * Proxy for undefined methods.  Default behavior is to throw an
	 * exception on undefined methods, however this function can be
	 * overridden to implement magic (dynamic) actions, or provide run-time
	 * dispatching.
	 *
	 * @param  string $methodName
	 * @param  array $args
	 * @throws Zend_Controller_Action_Exception
	 */
	public function __call($methodName, $args)
	{
		$this->errorHandler('Incorrect action: ' .
			preg_replace('/Action$/', '', $methodName));
		$this->_helper->json(['status' => 0, 'message' => 'Incorrect request alias']);
	}

	/**
	 * Implements "login" API action.
	 */
	public function loginAction()
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

			if (!$user || !password_verify($password, $user['password']))
			{
				throw new RuntimeException('Incorrect user email or password');
			}

			if ($user['Status'] != 'active')
			{
				throw new RuntimeException('User is not active');
			}

			$accessToken = (new Application_Model_Loginstatus)->save($user, true);
			Application_Model_User::updateInvites($user);
			$this->saveUserCache($user->toArray(), $accessToken);

			$response = [
				'status' => 'SUCCESS',
				'message' => 'AUTHENTICATED',
				'result' => [
					'id' => $user['id'],
					'karma' => Application_Model_User::getKarma($user),
					'Name' => $user['Name'],
					'Email_id' => $user['Email_id'],
					'Birth_date' => $user['Birth_date'],
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl(
							Application_Model_User::getThumb($user, '320x320')),
					'address' => Application_Model_Address::format($user),
					'latitude' => $user['latitude'],
					'longitude' => $user['longitude'],
					'Activities' => $user['interest'],
					'Gender' => Application_Model_User::getGender($user),
					'token' => $accessToken,
					'public_profile' => $user['public_profile']
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Authenticate user with facebook action.
	 */
	public function fbLoginAction()
	{
		try
		{
			$accessToken = $this->_request->getPost('token');

			if (trim($accessToken) === '')
			{
				throw new RuntimeException('Facebook access token cannot be blank');
			}

			$facebookApi = My_Facebook::getInstance([
				'default_access_token' => $accessToken
			]);

			$user = (new Application_Model_User)->facebookAuthentication($facebookApi);
			$accessToken = (new Application_Model_Loginstatus)->save($user, true);
			Application_Model_User::updateInvites($user);

			$response = [
				'status' => 'SUCCESS',
				'result' => [
					'id' => $user['id'],
					'karma' => Application_Model_User::getKarma($user),
					'Name' => $user['Name'],
					'Email_id' => $user['Email_id'],
					'Birth_date' => My_ArrayHelper::getProp($user, 'Birth_date'),
					'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
						Application_Model_User::getThumb($user, '320x320')),
					'address' => Application_Model_Address::format($user),
					'latitude' => $user['latitude'],
					'longitude' => $user['longitude'],
					'Activities' => My_ArrayHelper::getProp($user, 'interest'),
					'Gender' => Application_Model_User::getGender($user),
					'token' => $accessToken,
					'public_profile' => My_ArrayHelper::getProp($user, 'public_profile', 0)
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Register a user action.
	 */
	public function registrationAction()
	{
		try
		{
			$registrationForm = new Application_Form_Registration;

			if (!$registrationForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(My_Form::outputErrors($registrationForm));
			}

			$upload = new Zend_File_Transfer;
			$data = $registrationForm->getValues();
			$response = ['status' => 'SUCCESS'];

			if (count($upload->getFileInfo()))
			{
				$upload->setValidators([
					['Extension', false, ['jpg', 'jpeg', 'png', 'gif']],
					['MimeType', false, ['image/jpeg', 'image/png', 'image/gif'],
						['magicFile' => false]],
					['Count', false, 1]
				]);

				if (!$upload->isValid('image'))
				{
					throw new RuntimeException(implode('. ', $upload->getMessages()));
				}

				$ext = My_CommonUtils::$mimetype_extension[$upload->getMimeType('image')];

				do
				{
					$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
					$full_path = ROOT_PATH_WEB . '/www/upload/' . $name;
				}
				while (file_exists($full_path));

				$upload->addFilter('Rename', $full_path);
				$upload->receive();

				$image = (new Application_Model_Image)->save('www/upload', $name, $thumbs, [
					[[26,26], 'thumb26x26', 2],
					[[55,55], 'thumb55x55', 2],
					[[320,320], 'uploads']
				]);

				$data['image_id'] = $image['id'];
				$data['image_name'] = $name;

				$response['data']['thumb'] = $this->view->serverUrl() .
						$this->view->baseUrl('uploads/' . $name);
			}

			$user = (new Application_Model_User)->register($data +
				['Status' => 'active']);
			$accessToken = (new Application_Model_Loginstatus)
				->save($user, true);

			My_Email::send(
				$user['Email_id'],
				'seearound.me new Registration',
				[
					'template' => 'ws-registration',
					'settings' => $this->settings
				]
			);

			$response['data']['id'] = $user['id'];
			$response['data']['token'] = $accessToken;
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Reset password api gateway.
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

			$user = Application_Model_User::findByEmail($email);

			if (!$user)
			{
				throw new RuntimeException('No account found with that email address: ' .
					var_export($email, true));
			}

			if ($user['Status'] !== 'active')
			{
				throw new RuntimeException('This account is not active');
			}

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
				$email,
				'Forgot Password',
				[
					'template' => 'forgot-password',
					'assign' => ['code' => $confirmCode],
					'settings' => $this->settings
				]
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Friends list action.
	 */
	public function myfriendlistAction()
	{
		try
		{
			$user = $this->getUserByToken();
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

			$friends = (new Application_Model_Friends)->findAllByUserId($user,
				['limit' => 100, 'offset' => $start]);

			if ($friends->count())
			{
				foreach ($friends as $friend)
				{
					$alias = $friend['receiver_id'] == $user['id'] ?
						'sender_' : 'receiver_';

					$response['result'][] = [
						'id' => $friend[$alias.'id'],
						'Name' => $friend[$alias.'name'],
						'Email_id' => $friend[$alias.'email'],
						'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($friend,'320x320',['alias'=>$alias])),
					] + My_ArrayHelper::filter([
						'Birth_date' => $friend[$alias.'birthday'],
						'Gender' => Application_Model_User::getGender($friend, $alias),
						'Activities' => $friend[$alias.'interest']
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Follow user action.
	 */
	public function followAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$receiver_id = $this->_request->getPost('receiver_id');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver user ID value: ' .
					var_export($receiver_id, true));
			}

			if ($user['id'] == $receiver_id)
			{
				throw new RuntimeException('Receiver ID cannot be the same');
			}

			if (!Application_Model_User::checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect receiver user ID: ' .
					var_export($receiver_id, true));
			}

			$friendModel = new Application_Model_Friends;

			if ($friendModel->isFriend($user, $receiver))
			{
				throw new RuntimeException('User already in friend list');
			}

			$friendId = $friendModel->insert([
				'sender_id' => $user['id'],
				'receiver_id' => $receiver_id,
				'status' => $friendModel->status['confirmed'],
				'source' => 'herespy'
			]);

			(new Application_Model_FriendLog)->insert([
				'friend_id' => $friendId,
				'user_id' => $user['id'],
				'status_id' => $friendModel->status['confirmed']
			]);

			My_Email::send($receiver['Email_id'], 'New follower', [
				'template' => 'friend-invitation',
				'assign' => ['name' => $user['Name']],
				'settings' => $this->settings
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Unfollow user action.
	 */
	public function unfollowAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$receiver_id = $this->_request->getPost('receiver_id');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver user ID value: ' .
					var_export($receiver_id, true));
			}

			if ($user['id'] == $receiver_id)
			{
				throw new RuntimeException('Receiver ID cannot be the same');
			}

			if (!Application_Model_User::checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect receiver user ID: ' .
					var_export($receiver_id, true));
			}

			$friendModel = new Application_Model_Friends;
			$friend = $friendModel->isFriend($user, $receiver);

			if (!$friend)
			{
				throw new RuntimeException('User not found in friend list');
			}

			$friendModel->update([
				'status' => $friendModel->status['rejected']
			], 'id=' . $friend['id']);

			(new Application_Model_FriendLog)->insert([
				'friend_id' => $friend['id'],
				'user_id' => $user['id'],
				'status_id' => $friendModel->status['rejected']
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Profile details action.
	 */
  public function getotheruserprofileAction()
  {
		try
		{
			$user = $this->getUserByToken();
			$other_user_id = $this->_request->getPost('other_user_id');

			if (!v::intVal()->validate($other_user_id))
			{
				throw new RuntimeException('Incorrect other user ID value: ' .
					var_export($other_user_id, true));
			}

			if ($user['id'] == $other_user_id)
			{
				throw new RuntimeException('Other user ID cannot be the same');
			}

			$profile = Application_Model_User::findById($other_user_id, true);

			if ($profile == null)
			{
				throw new RuntimeException('Incorrect other user ID: ' .
					var_export($other_user_id, true));
			}

			$friendStatus = (new Application_Model_Friends)
				->isFriend($user, $profile);

			$response = [
				'status' => 'SUCCESS',
				'friends' => $friendStatus ? 1 : 0,
				'result' => My_ArrayHelper::filter([
					'id' => $profile['id'],
					'karma' => Application_Model_User::getKarma($profile),
					'Name' => $profile['Name'],
					'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
						Application_Model_User::getThumb($profile, '320x320')),
					'Email_id' => $profile['Email_id'],
					'Gender' => Application_Model_User::getGender($profile),
					'Activities' => $profile['interest'],
					'Birth_date' => $profile['Birth_date'],
					'public_profile' => $profile['public_profile']
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Profile details action.
	 */
  public function blockUserAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$block_user_id = $this->_request->getPost('block_user_id');

			if (!v::intVal()->validate($block_user_id))
			{
				throw new RuntimeException('Incorrect block user ID value: ' .
					var_export($block_user_id, true));
			}

			if ($user['id'] == $block_user_id)
			{
				throw new RuntimeException('Block user ID cannot be the same');
			}

			if (Application_Model_User::findById($block_user_id, true) == null)
			{
				throw new RuntimeException('Incorrect block user ID: ' .
					var_export($block_user_id, true));
			}

			$userBlockModel = new Application_Model_UserBlock;

			if ($userBlockModel->isBlock($user['id'], $block_user_id))
			{
				throw new RuntimeException('User is already blocked');
			}

			$userBlockModel->insert([
				'user_id' => $user['id'],
				'block_user_id' => $block_user_id
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Send message action.
	 */
	public function sendmessageAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$receiver_id = $this->_request->getPost('reciever_id');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver ID value: ' .
					var_export($receiver_id, true));
			}

			if ($user['id'] == $receiver_id)
			{
				throw new RuntimeException('Receiver ID cannot be the same');
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
			$isNewConversation = $conversation_id == null;

			if ($conversation_id)
			{
				if (!$conversationModel->checkId($conversation_id, $conversation))
				{
					throw new RuntimeException('Incorrect conversation ID: ' .
						var_export($conversation_id, true));
				}

				if ($conversation->from_id != $user['id'] && $conversation->to_id != $user['id'] ||
					$conversation->from_id != $receiver['id'] && $conversation->to_id != $receiver['id'])
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

				$conversation_id = $conversationModel->insert([
					'from_id' => $user['id'],
					'to_id' => $receiver['id'],
					'subject' => $subject,
					'created_at' => new Zend_Db_Expr('NOW()'),
					'status' => 0
				]);
			}

			$message_id = (new Application_Model_ConversationMessage)->insert([
				'conversation_id' => $conversation_id,
				'from_id' => $user['id'],
				'to_id' => $receiver['id'],
				'body' => $body,
				'is_first' => $isNewConversation ? 1 : 0,
				'is_read' => 0,
				'created_at' => new Zend_Db_Expr('NOW()'),
				'status' => 0
			]);

			$conversationSubject = $isNewConversation ? $subject : $conversation->subject;

			My_Email::send(
				[$receiver['Name'] => $receiver['Email_id']],
				$conversationSubject,
				[
					'template' => 'message-notification',
					'assign' => [
						'sender' => $user,
						'subject' => $conversationSubject,
						'message' => $body
					]
				]
			);

			$createdAt = new DateTime(!$isNewConversation ? $conversation->created_at : '');

			$response = [
				'status' => "SUCCESS",
				'message' => "Message Send Successfully",
				'result' => [
					'message_id' => $message_id,
					'conversation_id' => $conversation_id,
					'created' => $createdAt->setTimezone(Application_Model_User::getTimezone($user))
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * List of user unread messages action.
	 */
	public function unreadmessagesAction()
	{
		try
		{
			$user = $this->getUserByToken();
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
					'cm3.body',
					'cm3.created_at',
					'sender_id' => 'cm3.from_id',
					'receiver_id' => 'cm3.to_id',
				])
				->joinLeft(['cm1' => 'conversation_message'], '(cm1.conversation_id=c.id AND ' .
					'cm1.is_first=1)', '')
				->joinLeft(['cm3' => 'conversation_message'], '(cm3.conversation_id=c.id AND ' .
					'cm3.is_read=0 AND cm3.to_id=' . $user['id'] . ')', '')
				->where('cm3.id IS NOT NULL')
				->joinLeft(['us' => 'user_data'], 'us.id=cm3.from_id', [
					'sender_name' => 'Name',
					'sender_email' => 'Email_id',
					'sender_image_id' => 'image_id',
					'sender_image_name' => 'image_name',
				])
				->joinLeft(['ur' => 'user_data'], 'ur.id=cm3.to_id', [
					'receiver_name' => 'Name',
					'receiver_email' => 'Email_id',
					'receiver_image_id' => 'image_id',
					'receiver_image_name' => 'image_name',
				])
				->group('c.id')
				->order('cm3.created_at DESC')
				->limit(100, $start);

			$messages = $model->fetchAll($query);

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Message list Send Successfully'
			];

			if ($messages->count())
			{
				$userTimezone = Application_Model_User::getTimezone($user);
				foreach ($messages as $message)
				{
					$response['result'][] = [
						'id' => $message['id'],
						'sender_id' => $message['sender_id'],
						'subject' => $message['subject'],
						'message' => $message['body'],
						'created' => (new DateTime($message['created_at']))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'reciever_read' => 0,
						'senderName' => $message['sender_name'],
						'senderEmail' => $message['sender_email'],
						'senderImage' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320',
							['alias' => 'sender_'])),
						'receiverName' => $message['receiver_name'],
						'receiverEmail' => $message['receiver_email'],
						'receiverImage' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320',
							['alias' => 'receiver_'])),
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Retrieve message conversation action.
	 */
	public function messageConversationAction()
	{
		try
		{
			$user = $this->getUserByToken();
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

			if ($other_user_id != null)
			{
				if ($user['id'] == $other_user_id)
				{
					throw new RuntimeException('Other User ID cannot be the same');
				}

				if (!Application_Model_User::checkId($other_user_id, $other_user))
				{
					throw new RuntimeException('Incorrect other user ID: ' .
						var_export($other_user_id, true));
				}
			}

			$response = ['status' => 'SUCCESS'];

			$model = new Application_Model_Conversation;
			$query = $model->select()->setIntegrityCheck(false)
				->from(['c' => 'conversation'], [
					'c.id',
					'c.subject',
					'sender_id' => 'c.from_id',
					'receiver_id' => 'c.to_id',
					'cm1.body',
					'created_at' => 'IFNULL(cm3.created_at,cm1.created_at)',
					'is_read' => 'IFNULL(cm3.is_read,1)'
				])
				->joinLeft(['cm1' => 'conversation_message'], '(cm1.conversation_id=c.id AND ' .
					'cm1.is_first=1)', '')
				->joinLeft(['cm3' => 'conversation_message'], '(cm3.conversation_id=c.id AND ' .
					'cm3.is_read=0 AND cm3.to_id=' . $user['id'] . ')', '')
				->joinLeft(['us' => 'user_data'], 'us.id=c.from_id', [
					'sender_name' => 'Name',
					'sender_email' => 'Email_id',
					'sender_image_id' => 'image_id',
					'sender_image_name' => 'image_name',
				])
				->joinLeft(['ur' => 'user_data'], 'ur.id=c.to_id', [
					'receiver_name' => 'Name',
					'receiver_email' => 'Email_id',
					'receiver_image_id' => 'image_id',
					'receiver_image_name' => 'image_name',
				])
				->group('c.id')
				->order('c.created_at DESC')
				->limit(100, $start);

			if ($other_user_id)
			{
				$query->where('(c.to_id=' . $user['id'] . ' AND c.from_id=' . $other_user['id'] . ') OR ' .
					'(c.to_id=' . $other_user['id'] . ' AND c.from_id=' . $user['id'] . ')');
				$response['message'] = 'Inbox Message between two user rendered Successfully';
			}
			else
			{
				$query->where('c.to_id=' . $user['id'] . ' OR ' .
					'c.from_id=' . $user['id']);
				$response['message'] = 'Message list Send Successfully';
			}

			$messages = $model->fetchAll($query);

			if ($messages->count())
			{
				$userTimezone = Application_Model_User::getTimezone($user);
				foreach ($messages as $message)
				{
					$response['result'][] = [
						'id' => $message->id,
						'sender_id' => $message->sender_id,
						'receiver_id' => $message->receiver_id,
						'subject' => $message->subject,
						'message' => $message->body,
						'created' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'reciever_read' => $message->is_read,
						'senderName' => $message['sender_name'],
						'senderEmail' => $message['sender_email'],
						'senderImage' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320',
							['alias' => 'sender_'])),
						'receiverName' => $message['receiver_name'],
						'receiverEmail' => $message['receiver_email'],
						'receiverImage' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320',
							['alias' => 'receiver_'])),
						'formatted_date' => My_Time::time_ago($message->created_at,
							['ago' => true])
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Retrieve conversation details action.
	 */
	public function conversationAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect conversation ID value: ' .
					var_export($id, true));
			}

			$conversationModel = new Application_Model_Conversation;
			$conversation = $conversationModel->fetchRow(
				$query = $conversationModel->select()->setIntegrityCheck(false)
					->from(['c' => 'conversation'], [
						'c.*',
						'cm.body',
						'cm.from_id',
						'cm.to_id',
						'cm.is_read'
					])
					->where('c.id=?', $id)
					->joinLeft(['cm' => 'conversation_message'], '(cm.conversation_id=c.id AND ' .
						'cm.is_first=1)', '')
					->joinLeft(['us' => 'user_data'], 'us.id=cm.from_id', [
						'sender_name' => 'Name',
						'sender_email' => 'Email_id',
						'sender_image_id' => 'image_id',
						'sender_image_name' => 'image_name',
					])
					->joinLeft(['ur' => 'user_data'], 'ur.id=cm.to_id', [
						'receiver_name' => 'Name',
						'receiver_email' => 'Email_id',
						'receiver_image_id' => 'image_id',
						'receiver_image_name' => 'image_name',
					])
			);

			if ($conversation == null)
			{
				throw new RuntimeException('Incorrect conversation ID: ' .
					var_export($id, true));
			}

			if (!$conversationModel->canAccess($conversation, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$userTimezone = Application_Model_User::getTimezone($user);

			$response = [
				'status' => 'SUCCESS',
				'conversation' => [
					'id' => $conversation['id'],
					'subject' => $conversation['subject'],
					'message' => $conversation['body'],
					'created' =>  (new DateTime($conversation['created_at']))
						->setTimezone($userTimezone)
						->format(My_Time::SQL),
					'formatted_date' => My_Time::time_ago($conversation['created_at'],
						['ago' => true]),
					'reciever_read' => $conversation['is_read'],
					'sender_id' => $conversation['from_id'],
					'senderName' => $conversation['sender_name'],
					'senderEmail' => $conversation['sender_email'],
					'senderImage' => $this->view->serverUrl() . $this->view->baseUrl(
						Application_Model_User::getThumb($conversation, '320x320',
						['alias' => 'sender_'])),
					'receiver_id' => $conversation['to_id'],
					'receiverName' => $conversation['receiver_name'],
					'receiverEmail' => $conversation['receiver_email'],
					'receiverImage' => $this->view->serverUrl() . $this->view->baseUrl(
						Application_Model_User::getThumb($conversation, '320x320',
						['alias' => 'receiver_'])),
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Conversation messages list action.
	 */
	public function conversationMessageAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect conversation ID value: ' .
					var_export($id, true));
			}

			$conversationModel = new Application_Model_Conversation;

			if (!$conversationModel->checkId($id, $conversation))
			{
				throw new RuntimeException('Incorrect conversation ID: ' .
					var_export($id, true));
			}

			if ($user['id'] != $conversation->to_id && $user['id'] != $conversation->from_id)
			{
				throw new RuntimeException('You have no permissions to access this action');
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
				->joinLeft(['su' => 'user_data'], 'su.id=cm.from_id', '')
				->joinLeft(['ru' => 'user_data'], 'ru.id=cm.to_id', '')
				->order('cm.created_at DESC')
				->limit(10, $start);

			$messages = $messageModel->fetchAll($query);

			$response = ['status' => 'SUCCESS'];

			if ($messages->count())
			{
				$updateCondition = [];
				$userTimezone = Application_Model_User::getTimezone($user);
				foreach ($messages as $message)
				{
					$response['result'][] = [
						'id' => $message->id,
						'body' => $message->body,
						'created_at' => (new DateTime($message->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'sender_id' => $message->sender_id,
						'sender_name' => $message->sender_name,
						'sender_email' => $message->sender_email,
						'sender_image' =>  $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320', ['alias' => 'su_'])),
						'receiver_id' => $message->receiver_id,
						'receiver_name' => $message->receiver_name,
						'receiver_email' => $message->receiver_email,
						'receiver_image' =>  $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320', ['alias' => 'ru_'])),
						'is_read' => $message->is_read
					];

					if ($message->to_id == $user['id'])
					{
						$updateCondition[] = 'id=' . $message->id;
					}
				}

				if (count($updateCondition))
				{
					$messageModel->update(['is_read' => 1],
						implode(' OR ', $updateCondition));
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Set notificatations status action.
	 */
	public function viewedAction()
	{
		try
		{
			$user = $this->getUserByToken();
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

			$conversationIds = [];
			$conversationModel = new Application_Model_Conversation;

			foreach ($ids as $id)
			{
				$conversation = $conversationModel->findByID($id);

				if (!$conversation)
				{
					throw new RuntimeException('Incorrect conversation ID: ' .
						var_export($id, true));
				}

				switch ($user['id'])
				{
					case $conversation->from_id:
						break;
					case $conversation->to_id:
						$conversationIds[] = $conversation->id;
						break;
					default:
						throw new RuntimeException('You are not authorized to access this action');
				}
			}

			if (count($conversationIds))
			{
				$messageModel = new Application_Model_ConversationMessage;
				$messageModel->update(['is_read' => 1],
					'is_first=1 AND conversation_id IN (' . implode(',', $conversationIds) . ')');
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Read Inbox Message Successfully'
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Messages list action.
	 */
	public function messagesAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$other_user_id = $this->_request->getPost('other_user_id');

			if (!v::intVal()->validate($other_user_id))
			{
				throw new RuntimeException('Incorrect other user ID value: ' .
					var_export($other_user_id, true));
			}

			if ($user['id'] == $other_user_id)
			{
				throw new RuntimeException('Other user ID cannot be the same');
			}

			if (!Application_Model_User::checkId($other_user_id, $other_user))
			{
				throw new RuntimeException('Incorrect other user ID: ' .
					var_export($other_user_id, true));
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
				->where('(c.to_id=?',  $user['id'])
				->where('c.from_id=?)', $other_user_id)
				->orWhere('(c.to_id=?',  $other_user_id)
				->where('c.from_id=?)', $user['id'])
				->joinLeft(['su' => 'user_data'], 'su.id=cm.from_id', '')
				->joinLeft(['ru' => 'user_data'], 'ru.id=cm.to_id', '')
				->group('cm.id')
				->order('cm.created_at DESC')
				->limit(10, $start);

			$messages = $messageModel->fetchAll($query);

			$response = ['status' => 'SUCCESS'];

			if ($messages->count())
			{
				$userTimezone = Application_Model_User::getTimezone($user);
				foreach ($messages as $message)
				{
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
						'sender_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320', ['alias' => 'su_'])),
						'receiver_id' => $message->receiver_id,
						'receiver_name' => $message->receiver_name,
						'receiver_email' => $message->receiver_email,
						'receiver_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($message, '320x320', ['alias' => 'ru_']))
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Post details action.
	 */
	public function postAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$post_id = $this->_request->getPost('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			if (!Application_Model_News::checkId($post_id, $post,
						['link'=>true,'auth'=>$user,'userVote'=>true]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			// TODO: refactoring
			$friendStatus = $user['id'] != $post['user_id'] ? (new Application_Model_Friends)
				->isFriend($user, ['id' => $post['user_id']]) : null;

			$response = [
				'status' => 'SUCCESS',
				'post' => [
					'id' => $post->id,
					'user_id' => $post->user_id,
					'category_id' => $post->category_id,
					'news' => $post->news,
					'created_date' => My_Time::time_ago($post->created_date, ["ago" => true]),
					'latitude' => $post->latitude,
					'longitude' => $post->longitude,
					'Address' => Application_Model_Address::format($post) ?: $post->address,
					'street_name' => $post->street_name,
					'street_number' => $post->street_number,
					'city' => $post->city,
					'state' => $post->state,
					'country' => $post->country,
					'zip' => $post->zip,
					'comment_count' => $post->comment,
					'vote' => $post->vote,
					'isLikedByUser' => $post->user_vote !== null ? $post->user_vote : '0',
					'Name' => Application_Model_User::getFirstName($post, 'owner_'),
					'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
						Application_Model_User::getThumb($post, '320x320', ['alias' => 'owner_'])),
					'canEdit' => Application_Model_News::canEdit($post, $user) ? 1 : 0,
					'canVote' => Application_Model_Voting::canVote($user, $post) ? 1 : 0,
					'isFriend' => $friendStatus ? 1 : 0
				]
			];

			if ($post->image_id != null)
			{
				$response['post']['image'] = $this->view->serverUrl() .
					$this->view->baseUrl(Application_Model_News::getImage($post));
				$response['post']['thumb'] = $this->view->serverUrl() .
					$this->view->baseUrl(Application_Model_News::getThumb($post, '448x320'));
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
					$response['post']['link_thumb'] = $this->view->serverUrl() .
						$this->view->baseUrl(Application_Model_NewsLink::getThumb($post,
							'448x320', ['alias' => 'link_']));
					$response['post']['link_image'] = $this->view->serverUrl() .
						$this->view->baseUrl(Application_Model_NewsLink::getImage($post,
							['alias' => 'link_']));
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Add news action.
	 */
	public function addimobinewsAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$postForm = new Application_Form_Post;

			if (!$postForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(My_Form::outputErrors($postForm));
			}

			$postModel = new Application_Model_News;
			$postUser = $postModel->getPostUser($user);
			$postModel->save($postForm, $postUser,
				$address, $image, $thumbs, $link, $post);

			$response = [
				'status' => 'SUCCESS',
				'userid' => $postUser['id'],
				'post_id' => $post['id'],
				'category_id' => $post['category_id'],
				'message' => $post['news']
			];

			if ($link !== null)
			{
				$response['link_url'] = $link['link'];

				if (!empty($link['title']))
				{
					$response['link_title'] = $link['title'];
				}

				if (!empty($link['description']))
				{
					$response['link_description'] = $link['description'];
				}

				if (!empty($link['author']))
				{
					$response['link_author'] = $link['author'];
				}

				if (!empty($link['image_id']))
				{
					$response['link_thumb'] = $this->view->serverUrl() .
						$this->view->baseUrl(Application_Model_NewsLink::getThumb($link,
							'448x320'));
					$response['link_image'] = $this->view->serverUrl() .
						$this->view->baseUrl(Application_Model_NewsLink::getImage($link));
				}
			}

			if (!empty($post['image_id']))
			{
				$response['thumb'] = $this->view->serverUrl() .
					$this->view->baseUrl(Application_Model_News::getThumb($post, '448x320'));
				$response['image'] = $this->view->serverUrl() .
					$this->view->baseUrl(Application_Model_News::getImage($post));
			}

			(new Application_Model_User)->updateWithCache([
				'post' => $user['post']+1
			], $user);
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Before save post action.
	 */
	public function beforeSavePostAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$data = $this->_request->getPost();
			$postForm = new Application_Form_Post([
				'ignore' => ['address']
			]);

			if (!$postForm->isValid($data))
			{
				throw new RuntimeException(My_Form::outputErrors($postForm));
			}

			$linkModel = new Application_Model_NewsLink;
			$postLinks = $linkModel->parseLinks(
				Application_Model_News::filterBody($postForm->getValue('body')));
			$linkExist = null;

			if ($postLinks !== null)
			{
				foreach ($postLinks as $link)
				{
					$linkExist = $linkModel->findByLinkTrim($link);

					if ($linkExist !== null)
					{
						break;
					}
				}
			}

			$response = ['status' => 'SUCCESS'];

			if ($linkExist !== null)
			{
				$response['link_post_id'] = $linkExist->news_id;
				$response['link'] = $linkExist->link;
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Save post action.
	 */
	public function savePostAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$post_id = $this->_request->getPost('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			$postModel = new Application_Model_News;

			if (!$postModel->checkId($post_id, $post, ['link'=>true]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			if (!Application_Model_News::canEdit($post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$postForm = new Application_Form_Post;

			if (!$postForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(My_Form::outputErrors($postForm));
			}

			// TODO: remove after finish edit post category option
			$postForm->category_id->setValue($post['category_id']);

			$postModel->save($postForm, $user, $address,
				$image, $thumbs, $link, $post);

			$response = [
				'status' => 'SUCCESS',
				'post' => [
					'category_id' => $post['category_id'],
					'body' => $post['news'],
					'latitude' => $address['latitude'],
					'longitude' => $address['longitude']
				]
			];

			$addressFormat = Application_Model_Address::format($address);

			if ($addressFormat !== '')
			{
				$response['post']['address'] = $addressFormat;
			}

			if (!empty($address['street_name']))
			{
				$response['post']['street_name'] = $address['street_name'];
			}

			if (!empty($address['street_number']))
			{
				$response['post']['street_number'] = $address['street_number'];
			}

			if (!empty($address['city']))
			{
				$response['post']['city'] = $address['city'];
			}

			if (!empty($address['state']))
			{
				$response['post']['state'] = $address['state'];
			}

			if (!empty($address['country']))
			{
				$response['post']['country'] = $address['country'];
			}

			if (!empty($address['zip']))
			{
				$response['post']['zip'] = $address['zip'];
			}

			if (!empty($post['image_id']))
			{
				$response['post']['thumb'] = $this->view->serverUrl() .
					$this->view->baseUrl(Application_Model_News::getThumb($post,'448x320'));
				$response['post']['image'] = $this->view->serverUrl() .
					$this->view->baseUrl(Application_Model_News::getImage($post));
			}
			elseif ($link !== null)
			{
				$response['post']['link_url'] = $link['link'];

				if (!empty($link['title']))
				{
					$response['post']['link_title'] = $link['title'];
				}

				if (!empty($link['description']))
				{
					$response['post']['link_description'] = $link['description'];
				}

				if (!empty($link['author']))
				{
					$response['post']['link_author'] = $link['author'];
				}

				if (!empty($link['image_id']))
				{
					$response['post']['link_thumb'] = $this->view->serverUrl() .
						$this->view->baseUrl(Application_Model_NewsLink::getThumb($link,
							'448x320'));
					$response['post']['link_image'] = $this->view->serverUrl() .
						$this->view->baseUrl(Application_Model_NewsLink::getImage($link));
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Delete post action.
	 */
	public function deletePostAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$post_id = $this->_request->getPost('post_id');

			if (!v::intVal()->validate($post_id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($post_id, true));
			}

			if (!Application_Model_News::checkId($post_id, $post, ['join'=>false]))
			{
				throw new RuntimeException('Incorrect post ID: ' .
					var_export($post_id, true));
			}

			if (!Application_Model_News::canDelete($post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->update('news', [
				'isdeleted' => 1,
				'updated_date' => new Zend_Db_Expr('NOW()')
			], 'id=' . $post_id);

			(new Application_Model_User)->updateWithCache([
				'post' => $user['post']-1
			], $user);

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Edit profile action.
	 */
  public function editProfileAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$profileForm = new Application_Form_MobileProfile;

			if (!$profileForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(My_Form::outputErrors($profileForm));
			}

			$userModel = new Application_Model_User;
			$data = $profileForm->getValues();

			$updateData = [
				'Name' => $data['name'],
				'Birth_date' => trim($data['birth_date']) !== '' ?
					(new DateTime($data['birth_date']))->format('Y-m-d') : null,
				'Email_id' => $data['email'],
				'public_profile' => $data['public_profile'],
				'gender' => $data['gender'] != null ? array_search($data['gender'],
					Application_Model_User::$genderId) : null,
				'interest' => $userModel->filterInterest($data['interest'])
			];

			if (trim(My_ArrayHelper::getProp($data, 'image')) !== '')
			{
				if (!empty($user['image_id']))
				{
					$db = Zend_Db_Table::getDefaultAdapter();

					foreach ($userModel::$thumbPath as $path)
					{
						@unlink(ROOT_PATH_WEB . '/' . $path . '/' . $user['image_name']);
					}

					$db->delete('image_thumb', 'image_id=' . $user['image_id']);

					@unlink(ROOT_PATH_WEB . '/' . $userModel::$imagePath . '/' .
						$user['image_name']);

					$db->delete('image', 'id=' . $user['image_id']);
				}

				$image = (new Application_Model_Image)->save($userModel::$imagePath, $data['image'], $thumbs, [
					[[26,26], 'thumb26x26', 2],
					[[55,55], 'thumb55x55', 2],
					[[320,320], 'uploads']
				]);
				$updateData['image_id'] = $image['id'];
				$updateData['image_name'] = $data['image'];
				$profileImage = 'uploads/' . $data['image'];
			}
			else
			{
				$profileImage = Application_Model_User::getThumb($user, '320x320');
			}

			$userModel->updateWithCache($updateData, $user);

			$response = [
				'status' => 'SUCCESS',
				'message' => 'User profile has been updated successfully',
				'result' => My_ArrayHelper::filter([
					'user_id' => $user['id'],
					'karma' => Application_Model_User::getKarma($user),
					'Name' => $data['name'],
					'Email_id' => $data['email'],
					'address' => Application_Model_Address::format($user),
					'latitude' => $user['latitude'],
					'longitude' => $user['longitude'],
					'Profile_image' => $this->view->serverUrl() .
						$this->view->baseUrl($profileImage),
					'Gender' => $data['gender'],
					'Activities' => $updateData['interest'],
					'Birth_date' => $updateData['Birth_date'],
					'public_profile' => $data['public_profile']
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Flags post as inappropriate.
	 */
	public function flagPostAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$id = $this->_request->getPost('id');

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

			My_Email::send(
				$this->settings['email_fromAddress'],
				'Report Abuse',
				[
					'template' => 'flag-post',
					'assign' => ['post' => $post],
					'settings' => $this->settings
				]
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * List neares news action.
	 */
	public function requestNearestAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radious', 1.5),
				'start' => $this->_request->getPost('start', 0)
			];

			if (!$searchForm->isValid($searchParameters))
			{
				throw new RuntimeException(My_Form::outputErrors($searchForm));
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Nearest point data rendered successfully'
			];

			$result = (new Application_Model_News)
				->search($searchParameters + ['limit' => 15], [
					'auth' => $user,
					'link' => true,
					'userVote' => true
				]);

			if ($result->count())
			{
				$friendModel = new Application_Model_Friends;
				$userTimezone = Application_Model_User::getTimezone($user);

				foreach ($result as $row)
				{
					// TODO: refactoring
					$friendStatus = $user['id'] != $row['user_id'] ?
						$friendModel->isFriend($user, ['id' => $row['user_id']]) : null;

					$data = [
						'id' => $row->id,
						'user_id' => $row->user_id,
						'category_id' => $row->category_id,
						'news' => $row->news,
						'created_date' => My_Time::time_ago($row->created_date, ["ago" => true]),
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'Address' => Application_Model_Address::format($row) ?: $row->address,
						'street_name' => $row->street_name,
						'street_number' => $row->street_number,
						'city' => $row->city,
						'state' => $row->state,
						'country' => $row->country,
						'zip' => $row->zip,
						'comment_count' => $row->comment,
						'vote' => $row->vote,
						'isLikedByUser' => $row->user_vote !== null ? $row->user_vote : '0',
						'Name' => Application_Model_User::getFirstName($row, 'owner_'),
						'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($row, '320x320', ['alias' => 'owner_'])),
						'canEdit' => Application_Model_News::canEdit($row, $user) ? 1 : 0,
						'canVote' => Application_Model_Voting::canVote($user, $row) ? 1 : 0,
						'isFriend' => $friendStatus ? 1 : 0
					];

					if ($row->image_id)
					{
						$data['thumb'] = $this->view->serverUrl() .
							$this->view->baseUrl(Application_Model_News::getThumb($row, '448x320'));
						$data['image'] = $this->view->serverUrl() .
							$this->view->baseUrl(Application_Model_News::getImage($row));
					}

					if ($row->link_id)
					{
						$data['link_url'] = $row->link_link;

						if (trim($row->link_title) !== '')
						{
							$data['link_title'] = $row->link_title;
						}

						if (trim($row->link_description) !== '')
						{
							$data['link_description'] = $row->link_description;
						}

						if (trim($row->link_author) !== '')
						{
							$data['link_author'] = $row->link_author;
						}

						if ($row->link_image_id != null)
						{
							$data['link_thumb'] = $this->view->serverUrl() .
								$this->view->baseUrl(Application_Model_NewsLink::getThumb($row,
									'448x320', ['alias' => 'link_']));
							$data['link_image'] = $this->view->serverUrl() .
								$this->view->baseUrl(Application_Model_NewsLink::getImage($row,
									['alias' => 'link_']));
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * List user posts action.
	 */
	public function mypostsAction()
	{
		try
		{
			$user = $this->getUserByToken();

			$userId = $this->_request->getPost('user_id');

			if (!v::optional(v::intVal())->validate($userId))
			{
				throw new RuntimeException('Incorrect user ID value: ' .
					var_export($userId, true));
			}

			$queryOptions = [
				'auth' => $user,
				'link' => true,
				'userVote' => true
			];

			if ($userId != null)
			{
				if (!Application_Model_User::checkId($userId, $filterUser))
				{
					throw new RuntimeException('Incorrect user ID: ' .
						var_export($userId, true));

				}
				$queryOptions['user'] = $filterUser;
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radious', 1.5),
				'keywords' => $this->_request->getPost('searchText'),
				'filter' => $this->_request->getPost('filter'),
				'category_id' => (array) $this->_request->getPost('category_id'),
				'start' => $this->_request->getPost('start', 0)
			];

			if (!$searchForm->isValid($searchParameters))
			{
				throw new RuntimeException(My_Form::outputErrors($searchForm));
			}

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Posts rendred successfully'
			];

			$result = (new Application_Model_News)
				->search($searchParameters + ['limit' => 15], $queryOptions);

			if ($result->count())
			{
				$friendModel = new Application_Model_Friends;

				foreach ($result as $row)
				{
					// TODO: refactoring
					$friendStatus = $user['id'] != $row['user_id'] ?
						$friendModel->isFriend($user, ['id' => $row['user_id']]) : null;

					$data = [
						'id' => $row->id,
						'user_id' => $row->user_id,
						'category_id' => $row->category_id,
						'news' => $row->news,
						'created_date' => My_Time::time_ago($row->created_date, ["ago" => true]),
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'Address' => Application_Model_Address::format($row) ?: $row->address,
						'street_name' => $row->street_name,
						'street_number' => $row->street_number,
						'city' => $row->city,
						'state' => $row->state,
						'country' => $row->country,
						'zip' => $row->zip,
						'comment_count' => $row->comment,
						'vote' => $row->vote,
						'isLikedByUser' => $row->user_vote !== null ? $row->user_vote : '0',
						'Name' => Application_Model_User::getFirstName($row, 'owner_'),
						'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($row, '320x320', ['alias' => 'owner_'])),
						'canEdit' => Application_Model_News::canEdit($row, $user) ? 1 : 0,
						'canVote' => Application_Model_Voting::canVote($user, $row) ? 1 : 0,
						'isFriend' => $friendStatus ? 1 : 0
					];

					if ($row->image_id)
					{
						$data['thumb'] = $this->view->serverUrl() .
							$this->view->baseUrl(Application_Model_News::getThumb($row, '448x320'));
						$data['image'] = $this->view->serverUrl() .
							$this->view->baseUrl(Application_Model_News::getImage($row));
					}

					if ($row->link_id)
					{
						$data['link_url'] = $row->link_link;

						if (trim($row->link_title) !== '')
						{
							$data['link_title'] = $row->link_title;
						}

						if (trim($row->link_description) !== '')
						{
							$data['link_description'] = $row->link_description;
						}

						if (trim($row->link_author) !== '')
						{
							$data['link_author'] = $row->link_author;
						}

						if ($row->link_image_id != null)
						{
							$data['link_thumb'] = $this->view->serverUrl() .
								$this->view->baseUrl(Application_Model_NewsLink::getThumb($row,
									'448x320', ['alias' => 'link_']));
							$data['link_image'] = $this->view->serverUrl() .
								$this->view->baseUrl(Application_Model_NewsLink::getImage($row,
									['alias' => 'link_']));
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * List news comments action.
	 */
  public function getTotalCommentsAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->min(0)->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
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

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Comments rendred successfully',
			];

			$comments = (new Application_Model_Comments)->findAllByNewsId($post->id,[
				'limit' => 10,
				'start' => $start,
				'owner_thumbs' => [[320,320]]
			]);

			if ($comments->count())
			{
				$userTimezone = Application_Model_User::getTimezone($user);
				foreach ($comments as $comment)
				{
					$response['result'][] = [
						'id' => $comment->id,
						'news_id' => $post->id,
						'comment' => $comment->comment,
						'user_name' => $comment->owner_name,
						'user_id' => $comment->user_id,
						'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($comment, '320x320', ['alias' => 'owner_'])),
						'commTime' => (new DateTime($comment->created_at))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'commTimeAgo' => My_Time::time_ago($comment->created_at,
							["ago" => true]),
						'totalComments' => $post->comment,
						'canEdit' => Application_Model_Comments::canEdit($comment, $post, $user)
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Post news comment action.
	 */
	public function postCommentAction()
	{
		try
		{
			$user = $this->getUserByToken();
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

			$commentForm = new Application_Form_Comment;

			if (!$commentForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(My_Form::outputErrors($commentForm));
			}

			$data = $commentForm->getValues();
			$data['comment'] = Application_Model_News::filterBody($data['comment']);

			$commentModel = new Application_Model_Comments;
			$commentUser = $commentModel->getCommentUser($user);
			$comment_id = $commentModel->insert($data+[
				'user_id' => $commentUser['id'],
				'news_id' => $id,
				'created_at' => new Zend_Db_Expr('NOW()'),
				'updated_at' => new Zend_Db_Expr('NOW()')
			]);

			$postComments = $post['comment']+1;

			(new Application_Model_News)->update([
				'comment' => $postComments
			], 'id=' . $id);

			$updateUser = ['comment' => $commentUser['comment']+1];

			if ($post['user_id'] != $commentUser['id'])
			{
				$updateUser['comment_other'] = $commentUser['comment_other']+1;
			}

			(new Application_Model_User)
				->updateWithCache($updateUser, $commentUser);

			$response = [
				'status' => 'SUCCESS',
				'message' => 'Comments Post Successfully',
				'result' => [
					'id' => $comment_id,
					'news_id' => $id,
					'comment' => $data['comment'],
					'user_name' => $commentUser['Name'],
					'user_id' => $commentUser['id'],
					'Profile_image' => $this->view->serverUrl() . $this->view->baseUrl(
						Application_Model_User::getThumb($commentUser, '320x320')),
					'commTime' => (new DateTime)
						->setTimezone(Application_Model_User::getTimezone($commentUser))
						->format(My_Time::SQL),
					'commTimeAgo' => 'Just now',
					'totalComments' => $postComments
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
  }

	/**
	 * Delete post comment action.
	 */
  public function deleteCommentAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$id = $this->_request->getPost('comment_id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect comment ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_Comments::checkId($id, $comment,
				['post' => ['post_user_id' => 'user_id', 'post_comment' => 'comment']]))
			{
				throw new RuntimeException('Incorrect comment ID: ' .
					var_export($id, true));
			}

			$post = [
				'user_id' => $comment['post_user_id'],
				'comment' => $comment['post_comment']
			];

			if (!Application_Model_Comments::canEdit($comment, $post, $user))
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			(new Application_Model_Comments)->update([
				'isdeleted' => 1,
				'updated_at' => new Zend_Db_Expr('NOW()')
			], 'id=' . $comment['id']);

			(new Application_Model_News)->update([
				'comment' => $post['comment']-1
			], 'id=' . $comment['news_id']);

			$updateUser = ['comment' => $user['comment']-1];

			if ($post['user_id'] != $user['id'])
			{
				$updateUser['comment_other'] = $user['comment_other']-1;
			}

			(new Application_Model_User)
				->updateWithCache($updateUser, $user);

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response =[
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
     }

	/**
	 * Function to add like to a news.
	 */
	public function postLikeAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$vote = $this->_request->getPost('vote');

			if (!v::intVal()->oneOf(v::equals(-1),v::equals(1))->validate($vote))
			{
				throw new RuntimeException('Incorrect vote value: ' .
					var_export($vote, true));
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

			$voteModel = new Application_Model_Voting;

			if (!$voteModel->canVote($user, $post))
			{
				throw new RuntimeException('You cannot vote this post');
			}

			$userVote = $voteModel->findVote($post->id, $user['id']);

			if ($userVote != null)
			{
				$voteModel->update([
					'updated_at' => new Zend_Db_Expr('NOW()'),
					'active' => 0
				], 'id=' . $userVote->id);
			}

			$updateVote = null;

			if (!$user['is_admin'] && $userVote != null)
			{
				$updateVote = $post->vote - $userVote->vote;
			}

			if ($user['is_admin'] || !$userVote || $userVote->vote != $vote)
			{
				$voteModel->insert([
					'vote' => $vote,
					'user_id' => $user['id'],
					'news_id' => $post->id,
					'active' => 1
				]);

				$updateVote = $post->vote + $vote;
				$activeVote = $vote;
			}
			else
			{
				$activeVote = 0;
			}

			if ($updateVote !== null)
			{
				(new Application_Model_News)
					->update(['vote' => $updateVote], 'id=' . $id);

				(new Application_Model_User)->updateWithCache([
					'vote' => $user['vote']+$vote
				], $user);
			}

			$response = [
				'success' => 'voted successfully',
				'vote' => $updateVote !== null ? $updateVote : $post->vote,
				'active' => $activeVote
			];
    }
		catch (Exception $e)
		{
			$response = array(
				'resonfailed' => 'Sorry unable to vote',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * List user notifications action.
	 */
	public function notificationAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$start = $this->_request->getPost('start', 0);

			if (!v::optional(v::intVal())->min(0)->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$response = ['status' => 'SUCCESS'];

			$db = Zend_Db_Table::getDefaultAdapter();
			$maxDate = $db->quote((new DateTime)->modify('-15 days')->setTime(0, 0)
				->format(My_Time::SQL));

			$friendSelect = $db->select();
			$friendSelect->from(['f' => 'friends'], [
				'f.id',
				'type' => new Zend_Db_Expr('"friend"'),
				'fl.created_at',
				'param1' => new Zend_Db_Expr('NULL'),
				'param2' => new Zend_Db_Expr('NULL'),
				'param3' => new Zend_Db_Expr('NULL'),
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'user_image_id' => 'u.image_id',
				'user_image_name' => 'u.image_name',
				'is_read' => 'f.notify'
			]);
			$friendSelect->where('f.receiver_id=? AND f.status=1', $user['id']);
			$friendSelect->where('fl.created_at>' . $maxDate);
			$friendSelect->joinLeft(['fl' => 'friend_log'],
				'fl.friend_id=f.id AND fl.status_id=f.status', '');
			$friendSelect->joinLeft(['u' => 'user_data'], 'u.id=fl.user_id', '');

			$messageSelect = $db->select();
			$messageSelect->from(['cm' => 'conversation_message'], [
				'id' => 'cm.id',
				'type' => new Zend_Db_Expr('"message"'),
				'cm.created_at',
				'param1' => 'cm.conversation_id',
				'param2' => new Zend_Db_Expr('NULL'),
				'param3' => new Zend_Db_Expr('NULL'),
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'user_image_id' => 'u.image_id',
				'user_image_name' => 'u.image_name',
				'is_read' => 'cm.is_read'
			]);
			$messageSelect->where('cm.to_id=?', $user['id']);
			$messageSelect->where('cm.created_at>' . $maxDate);
			$messageSelect->joinLeft(['u' => 'user_data'], 'u.id=cm.from_id', '');

			$postLikeSelect = $db->select();
			$postLikeSelect->from(['n' => 'news'], [
				'id' => 'max(v.id)',
				'type' => new Zend_Db_Expr('"vote"'),
				'created_at' => 'max(v.created_at)',
				'param1' => 'n.id',
				'param2' => 'count(distinct v.id)',
				'param3' => 'v.bot_id',
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'user_image_id' => 'u.image_id',
				'user_image_name' => 'u.image_name',
				'is_read' => 'min(v.is_read)'
			])
			->joinLeft(['v' => 'votings'], 'v.news_id=n.id', '')
			->joinLeft(['v2' => 'votings'], 'v2.id=(' .
				'SELECT v3.id FROM votings AS v3 WHERE ' .
					'v3.news_id=n.id AND ' .
					'v3.user_id IS NOT NULL AND ' .
					'v3.user_id<>' . $user['id'] .
					' ORDER BY v3.created_at DESC' .
					' LIMIT 1' .
			')', '')
			->joinLeft(['u' => 'user_data'], 'u.id=v2.user_id', '')
			->where(
				'n.isdeleted=0 AND n.user_id=' . $user['id'] . ' AND ' .
				'v.created_at>' . $maxDate . ' AND v.active=1 AND ' .
				'(v.user_id IS NULL OR v.user_id<>' .  $user['id'] . ') AND ' .
				'(v2.id IS NULL OR (v2.active=1 AND v2.created_at>' . $maxDate . '))'
			)->group('n.id');

			$postCommentSelect = $db->select();
			$postCommentSelect->from(['n' => 'news'], [
				'c.id',
				'type' => new Zend_Db_Expr('"comment"'),
				'c.created_at',
				'param1' => 'n.id',
				'param2' => new Zend_Db_Expr('NULL'),
				'param3' => new Zend_Db_Expr('NULL'),
				'user_id' => 'u.id',
				'user_name' => 'u.Name',
				'user_image_id' => 'u.image_id',
				'user_image_name' => 'u.image_name',
				'is_read' => 'c.is_read'
			]);
			$postCommentSelect->where('n.isdeleted=0 AND n.user_id=?', $user['id']);
			$postCommentSelect->joinLeft(['c' => 'comments'], 'c.news_id=n.id', '');
			$postCommentSelect->where('c.isdeleted=0 AND c.user_id<>?', $user['id']);
			$postCommentSelect->where('c.created_at>' . $maxDate);
			$postCommentSelect->joinLeft(['u' => 'user_data'], 'u.id=c.user_id', '');

			$select = $db->select()
				->union([
					$friendSelect,
					$messageSelect,
					$postLikeSelect,
					$postCommentSelect
				], Zend_Db_Select::SQL_UNION_ALL)
				->order('created_at DESC')
				->limit(10, $start);

			$result = $db->fetchAll($select);

			if (count($result))
			{
				$userTimezone = Application_Model_User::getTimezone($user);

				foreach ($result as $row)
				{
					$data = [
						'id' => $row['id'],
						'type' => $row['type'],
						'is_read' => $row['is_read'],
						'created_at' => (new DateTime($row['created_at']))
							->setTimezone($userTimezone)
							->format(My_Time::SQL),
						'user_id' => $row['user_id'],
						'user_name' => $row['user_name'],
						'user_image' => $this->view->serverUrl() . $this->view->baseUrl(
							Application_Model_User::getThumb($row, '320x320',
								['alias' => 'user_']))
					];

					switch ($row['type'])
					{
						case 'friend':
							$data['message'] = $row['user_name'] .
								' started following you';
							break;
						case 'message':
							$data['conversation_id'] = $row['param1'];
							$data['message'] = $row['user_name'] .
								' sent you a new message';
							break;
						case 'vote':
							if ($data['user_name'] == null)
							{
								$data['user_name'] = My_arrayHelper::getProp(
									Application_Model_Voting::$botNames, $row['param3']);
							}

							$message = $data['user_name'];

							if ($row['param2'] > 1)
							{
								$message .= ' and ';

								if ($row['param2'] == 2)
								{
									$message .= 'one other';
								}
								else
								{
									$formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
									$message .= $formatter->format($row['param2'] - 1) . ' others';
								}
							}

							$data['message'] = $message . ' liked your post';
							$data['post_id'] = $row['param1'];
							break;
						case 'comment':
							$data['post_id'] = $row['param1'];
							$data['message'] = $row['user_name'] .
								' commented on your post';
							break;
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
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Set notifications read status action.
	 */
	public function notificationReadAction()
	{
		try
		{
			$user = $this->getUserByToken();
			$id = $this->_request->getPost('id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect notification ID value: ' .
					var_export($id, true));
			}

			$type = $this->_request->getPost('type');

			if (!v::stringType()->validate($type))
			{
				throw new RuntimeException('Incorrect notification type value: ' .
					var_export($type, true));
			}

			switch ($type)
			{
				case 'friend':
					$friendModel = new Application_Model_Friends;
					$friendRequest = $friendModel->findById($id);

					if ($friendRequest == null)
					{
						throw new RuntimeException('Incorrect friend request ID: ' .
							var_export($id, true));
					}

					if ($user['id'] != $friendRequest->receiver_id)
					{
						throw new RuntimeException('You are not authorized to access this action');
					}

					$friendModel->update(['notify' => 1], 'id=' . $id);
					break;
				case 'message':
					$messageModel = new Application_Model_ConversationMessage;
					$message = $messageModel->findById($id);

					if ($message == null)
					{
						throw new RuntimeException('Incorrect message ID: ' .
							var_export($id, true));
					}

					if ($user['id'] != $message->to_id)
					{
						throw new RuntimeException('You are not authorized to access this action');
					}

					$messageModel->update(['is_read' => 1], 'id=' . $id);
					break;
				case 'vote':
					$voteModel = new Application_Model_Voting;
					$vote = $voteModel->findById($id, [
						'post' => ['post_user_id' => 'user_id']
					]);

					if ($vote == null)
					{
						throw new RuntimeException('Incorrect vote ID: ' .
							var_export($id, true));
					}

					if ($user['id'] == $vote['user_id'] ||
						$user['id'] != $vote['post_user_id'])
					{
						throw new RuntimeException('You are not authorized to access this action');
					}

					$voteModel->update(['is_read' => 1], [
						'news_id=?' => $vote['news_id'],
						'created_at<=?' => $vote['created_at']
					]);
					break;
				case 'comment':
					$commenModel = new Application_Model_Comments;
					if (!$commenModel->checkId($id, $comment,
						['post' => ['post_user_id' => 'user_id']]))
					{
						throw new RuntimeException('Incorrect comment ID: ' .
							var_export($id, true));
					}

					if ($user['id'] == $comment['user_id'] ||
						$user['id'] != $comment['post_user_id'])
					{
						throw new RuntimeException('You are not authorized to access this action');
					}

					$commenModel->update(['is_read' => 1], 'id=' . $id);
					break;
				default:
					throw new RuntimeException('Incorrect notification type: ' .
						var_export($type, true));
			}

			$response = ['status' => 'SUCCESS'];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
			$this->errorHandler($e);
		}

		$this->responseHandler($response);
		$this->_helper->json($response);
	}

	/**
	 * Checks if access token is valid and finds user.
	 *
	 * @return Zend_Db_Table_Row_Abstract
	 * @throws RuntimeException
	 */
	protected function getUserByToken()
	{
		$token = $this->_request->getPost('token');

		if (!v::stringType()->length(64,64)->validate($token))
		{
			throw new RuntimeException('Incorrect access token value: ' .
				var_export($token, true));
		}

		$cache = Zend_Registry::get('cache');
		$user = $cache->load('user_' . $token);

		if ($user == null)
		{
			$user = $user = (new Application_Model_User)->findUserByToken($token);

			if ($user == null || $user['Status'] != 'active')
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$this->saveUserCache($user->toArray(), $token, $cache);
		}

		(new Application_Model_Loginstatus)->update([
			'visit_time' => new Zend_Db_Expr('NOW()')
		], ['token=?' => $token]);

		return $user;
	}

	/**
	 * Saves user data to cache.
	 *
	 * @param mixed $user
	 * @param string $token
	 * @param Zend_Cache $cache
	 */
	protected function saveUserCache($user, $token, $cache=null)
	{
		$cache = $cache ?: Zend_Registry::get('cache');
		$cache->save($user, 'user_' . $token, ['user' . $user['id']]);
	}

	/**
	 * Error handler method.
	 *
	 * @param mixed $error Exception or string
	 */
	protected function errorHandler($error)
	{
		$logger = new Zend_Log(new Zend_Log_Writer_Stream(ROOT_PATH . '/log' .
			'/api_error_' . date('Y-m-d') . '.log'));
		$logger->info($_SERVER['REQUEST_URI'] .
			"\n>> " . var_export($_REQUEST, true) .
			"\n<< " . $error .
			"\n\$_SERVER: " . var_export($_SERVER, true));
	}

	/**
	 * Response handler method.
	 *
	 * @param array $response Response data
	 */
	protected function responseHandler($response)
	{
		$logger = new Zend_Log(new Zend_Log_Writer_Stream(ROOT_PATH . '/log' .
			'/api_response_' . date('Y-m-d') . '.log'));
		$logger->info($_SERVER['REQUEST_URI'] .
			"\n>> " . var_export($_REQUEST, true) .
			"\n<< " . var_export($response, true) .
			"\n\$_SERVER: " . var_export($_SERVER, true));
	}
}
