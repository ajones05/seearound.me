<?php
require_once ROOT_PATH . '/vendor/autoload.php';

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

			if (My_Validate::emptyString($email))
			{
				throw new RuntimeException('Email cannot be blank', -1);
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				throw new RuntimeException('Incorrect email address: ' . var_export($email, true), -1);
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

			if ($user->Status != 'active')
			{
				throw new RuntimeException('User is not active', -1);
			}

			$user->updateToken();

			$loginStatus = new Application_Model_Loginstatus;
			$login_id = $loginStatus->insert(array(
				'user_id' => $user->id,
				'login_time' => new Zend_Db_Expr('NOW()'),
				'ip_address' => $_SERVER['REMOTE_ADDR'])
			);

			// TODO: check
			// Calculation for the invites counts for the login user
			if (date('D') == 'Mon')
			{
				$inviteStatusRow = Application_Model_Invitestatus::getInstance()->getData(array(
					'user_id' => $user->id
				));

				if ($inviteStatusRow != null && floor((time() - strtotime($inviteStatusRow->updated)) / 86400) >= 7)
				{
					$loginRows = $loginStatus->sevenDaysOldData($user->id);
					$inviteStatusRow->invite_count = $inviteStatusRow->invite_count + floor(count($loginRows) / 5);
					$inviteStatusRow->updated = new Zend_Db_Expr('NOW()');
					$inviteStatusRow->save();
				}
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'AUTHENTICATED',
				'result' => array(
					'id' => $user->id,
					'Name' => $user->Name,
					'Email_id' => $user->Email_id,
					'Birth_date' => $user->Birth_date,
					'Profile_image' => $this->view->serverUrl() . $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
					'Status' => $user->Status,
					'Token' => $user->Token,
					'address' => $user->address(),
					'latitude' => $user->lat(),
					'longitude' => $user->lng(),
					'Activities' => $user->activities(),
					'Gender' => $user->gender(),
					'login_id' => $login_id,
				)
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
	 * Authenticate user with facebook action.
	 *
	 * @return void
	 */
	public function fbLoginAction()
	{
		try
		{
			$token = $this->_request->getPost('access-token');

			if (trim($token) === '')
			{
				throw new RuntimeException('Facebook access token cannot be blank', -1);
			}

			$config = Zend_Registry::get('config_global');

			Facebook\FacebookSession::setDefaultApplication($config->facebook->app->id, $config->facebook->app->secret);

			$session = new Facebook\FacebookSession($token);

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

			$response = array(
				'status' => 'SUCCESS',
				'result' => array(
					'login_id' => $login_id
				)
			);
		}
		catch (Exception $e)
		{
			if ($e instanceof RuntimeException || $e instanceof Facebook\FacebookAuthorizationException)
			{
				$message = $e->getMessage();
			}
			else
			{
				$message = 'Internal Server Error';
			}

			$response = array(
				'status' => 'FAILED',
				'message' => $message
			);
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

			$user = (new Application_Model_User)->register(
				array_merge(
					$form->getValues(),
					array(
						'Status' => 'active'
					)
				)
			);

			$user->updateToken();

			My_Email::send(
				$user->Email_id,
				'seearound.me new Registration',
				array('template' => 'ws-registration')
			);

			$login_id = (new Application_Model_Loginstatus)->insert(array(
				'user_id' => $user->id,
				'login_time' => new Zend_Db_Expr('NOW()'),
				'ip_address' => $_SERVER['REMOTE_ADDR'])
			);

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
	 * Friends list action.
	 *
	 * @return void
	 */
	public function myfriendlistAction() 
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'My Friend list rendered successfully'
			);

			// TODO: add limit/start

			$friends = (new Application_Model_Friends)->findAllByUserId($user->id, 100, 0);

			if (count($friends))
			{
				foreach ($friends as $friend)
				{
					$_user = $friend->reciever_id == $user->id ?
						$friend->findDependentRowset('Application_Model_User', 'FriendSender')->current() :
						$friend->findDependentRowset('Application_Model_User', 'FriendReceiver')->current();

					$response['result'][] = My_ArrayHelper::filter(array(
						'id' => $_user->id,
						'Name' => $_user->Name,
						'Email_id' => $_user->Email_id,
						'Profile_image' => $this->view->serverUrl() . $_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'Birth_date' => $_user->Birth_date,
						'Gender' => $_user->gender(),
						'Activities' => $_user->activities()
					));
				}
			}
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
	 * Profile details action.
	 *
	 * @return void
	 */
    public function getotheruserprofileAction()
    {
		try
		{
			$other_user_id = $this->_request->getPost('other_user_id');

			if (!Application_Model_User::checkId($other_user_id, $other_user))
			{
				throw new RuntimeException('Incorrect other user ID', -1);
			}
			
			$response = array(
				'status' => 'SUCCESS',
				'result' => My_ArrayHelper::filter(array(
					'id' => $other_user->id,
					'Name' => $other_user->Name,
					'Profile_image' => $this->view->serverUrl() . $other_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
					'Email_id' => $other_user->Email_id,
					'Gender' => $other_user->gender(),
					'Activities' => $other_user->activities(),
					'Birth_date' => $other_user->Birth_date
				))
			);

			$user_id = $this->_request->getPost('user_id');

			if ($user_id != null)
			{
				if ($user_id == $other_user_id)
				{
					throw new RuntimeException('Other user ID cannot be the same as your ID', -1);
				}

				if (!Application_Model_User::checkId($user_id, $user))
				{
					throw new RuntimeException('Incorrect user ID', -1);
				}

				$result = (new Application_Model_Friends)->getStatus($user_id, $other_user_id);

				$response['friends'] = count($result) && $result->status == 1 ? 1 : 0;
			}
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
	 * Send message action.
	 *
	 * @return void
	 */
	public function sendmessageAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('sender_id'), $sender))
			{
				throw new RuntimeException('Incorrect sender ID', -1);
			}

			if (!Application_Model_User::checkId($this->_request->getPost('reciever_id'), $receiver))
			{
				throw new RuntimeException('Incorrect reciever ID', -1);
			}

			$subject = $this->_request->getPost('subject');

			if (My_Validate::emptyString($subject))
			{
				throw new RuntimeException('Incorrect subject value', -1);
			}

			$body = $this->_request->getPost('message');

			if (My_Validate::emptyString($body))
			{
				throw new RuntimeException('Incorrect message value', -1);
			}

			$message = (new Application_Model_Message)->createRow(array(
				'sender_id' => $sender->id,
				'receiver_id' => $receiver->id,
				'subject' => $subject,
				'message' => $body,
				'created' => new Zend_Db_Expr('NOW()'),
				'updated' => new Zend_Db_Expr('NOW()'),
				'is_deleted' => 'false',
				'is_valid' => 'true',
				'sender_read' => 'true',
				'reciever_read' => 'false',
			));

			$message->save();

			$response = array(
				'status' => "SUCCESS",
				'message' => "Message Send Successfully",
				'result' => array(
					'id' => $message->id,
					'created' => $message->created
				)
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
	 * Fetch list of user messages action.
	 *
	 * @return void
	 */
	public function listmessageAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('Incorrect user ID', -1);
			}

			$model = new Application_Model_Message;

			$messages = $model->fetchAll(
				$model->publicSelect()
					->where('message.receiver_id =?', $user->id)
					->order('updated DESC')
					// TODO: limit/start
					->limit(100, null)
			);

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Message list Send Successfully'
			);

			if (count($messages))
			{
				foreach ($messages as $message)
				{
					$user = $message->findDependentRowset('Application_Model_User', 'Receiver')->current();

					$response['result'][] = array(
						'id' => $message->id,
						'sender_id' => $message->sender_id,
						'subject' => $message->subject,
						'message' => $message->message,
						'created' => $message->created,
						'updated' => $message->updated,
						'reciever_read' => $message->reciever_read,
						'Name' => $user->Name,
						'Email_id' => $user->Email_id,
						'Profile_image' => $this->view->serverUrl() . $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
					);
				}
			}
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
	 * List of user unread messages action.
	 *
	 * @return void
	 */
	public function unreadmessagesAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Message list Send Successfully'
			);

			$model = new Application_Model_Message;

			$messages = $model->fetchAll(
				$model->publicSelect()
					->where('receiver_id =?', $user->id)
					->where('reciever_read =?', 'false')
					->order('updated DESC')
					// TODO: limit/start
					->limit(100, 0)
			);

			if (count($messages))
			{
				foreach ($messages as $message)
				{
					$sender = $message->findDependentRowset('Application_Model_User', 'Sender')->current();

					$response['result'][] = array(
						'id' => $message->id,
						'sender_id' => $message->sender_id,
						'subject' => $message->subject,
						'message' => $message->message,
						'created' => $message->created,
						'updated' => $message->updated,
						'reciever_read' => $message->reciever_read,
						'Name' => $sender->Name,
						'Email_id' => $sender->Email_id,
						'Profile_image' => $this->view->serverUrl() .
							$sender->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
					);
				}
			}
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
	 * Retrieve message conversation action.
	 * 
	 * @return	void
	 */
	public function messageConversationAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			if (!Application_Model_User::checkId($this->_request->getPost('other_user_id'), $other_user))
			{
				throw new RuntimeException('Incorrect other user ID', -1);
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Inbox Message between two user rendered Successfully'
			);

			$model = new Application_Model_Message;
			$messages = $model->fetchAll(
				$model->publicSelect()
					->where('message.receiver_id =?',  $user->id)
					->where('message.sender_id =?', $other_user->id)
					->orWhere('message.receiver_id =?',  $other_user->id)
					->where('message.sender_id =?', $user->id)
					->order('updated ASC')
					// TODO: limit/start
					->limit(100, 0)
			);

			if (count($messages))
			{
				foreach ($messages as $message)
				{
					$sender = $message->findDependentRowset('Application_Model_User', 'Sender')->current();

					$response['result'][] = array(
						'id' => $message->id,
						'sender_id' => $message->sender_id,
						'subject' => $message->subject,
						'message' => $message->message,
						'created' => $message->created,
						'updated' => $message->updated,
						'reciever_read' => $message->reciever_read,
						'Name' => $sender->Name,
						'Email_id' => $sender->Email_id,
						'Profile_image' => $this->view->serverUrl() .
							$sender->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
					);
				}
			}
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
	 * Set notificatations status action.
	 * 
	 * @return	void
	 */
	public function viewedAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$ids = explode(",", $this->_request->getPost('post_id'));

			$model = new Application_Model_Message;

			foreach ($ids as $id)
			{
				$message = $model->findByID($id);

				if (!$message)
				{
					throw new RuntimeException('Incorrect message ID: ' . var_export($id, true), -1);
				}

				switch ($user->id)
				{
					case $message->receiver_id:
						$message->reciever_read = 'true';
						break;
					case $message->sender_id:
						$message->sender_read = 'true';
						break;
					default:
						throw new RuntimeException('You are not authorized to access this action', -1);
				}

				$message->save();
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
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
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
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$form = new Application_Form_News;

			if (!$form->isValid($this->_request->getPost()))
			{
				$this->_formValidateException($form);
			}

			$news = (new Application_Model_News)->save(array_merge($form->getValues(), array('user_id' => $user->id)));

			$response = array(
				'status' => 'SUCCESS',
				'message' => $news->news,
				'userid' => $user->id
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
	 * Edit profile action.
	 * 
	 * @return	void
	 */
    public function editProfileAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$model = new Application_Model_User;
			$form = new Application_Form_MobileProfile;

			if (!$form->isValid($this->_request->getPost()))
			{
				$this->_formValidateException($form);
			}

			$data = $form->getValues();

			$model->getDefaultAdapter()->beginTransaction();

			try
			{
				$user_data = array(
					'Name' => $data['name'],
					'Birth_date' => trim($data['birth_date']) !== '' ? $data['birth_date'] : null,
					// 'Email_id' => $data['email']
				);

				if (trim(My_ArrayHelper::getProp($data, 'image')) !== '')
				{
					$user_data['Profile_image'] = $user->Profile_image = $data['image'];
				}

				$model->update($user_data, $model->getDefaultAdapter()->quoteInto('id =?', $user->id));

				$profile = $user->findDependentRowset('Application_Model_UserProfile')->current();

				if (!$profile)
				{
					$profile = (new Application_Model_UserProfile)->createRow(array('user_id' => $user->id));
				}

				$profile->public_profile = $data['public_profile'];
				$profile->Activities = $data['activities'];
				$profile->Gender = $data['gender'];
				$profile->save();

				$model->getDefaultAdapter()->commit();
			}
			catch (Exception $e)
			{
				$model->getDefaultAdapter()->rollBack();

				throw $e;
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'User profile has been updated successfully',
				'result' => My_ArrayHelper::filter(array(
					'user_id' => $user->id,
					'Name' => $data['name'],
					'Email_id' => $data['email'],
					'address' => $user->address(),
					'latitude' => $user->lat(),
					'longitude' => $user->lng(),
					'Profile_image' => $this->view->serverUrl() . $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
					'Gender' => $data['gender'],
					'Activities' => $data['activities'],
					'Birth_date' => $data['birth_date']
				)
			));
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
	 * List neares news action.
	 * 
	 * @return	void
	 */
	public function requestNearestAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('userId'), $user))
			{
				throw new RuntimeException('Incorrect user id', -1);
			}

			$latitude = $this->_request->getPost('latitude');

			if (!is_numeric($latitude) || !My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value', -1);
			}

			$longitude = $this->_request->getPost('longitude');

			if (!is_numeric($longitude) || !My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value', -1);
			}

			$radius = $this->_request->getPost('radious', 1);

			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value', -1);
			}

			$start = $this->_request->getPost('fromPage', 0);

			if (!My_Validate::digit($start) || $start < 0)
			{
				throw new RuntimeException('Incorrect start value', -1);
			}

			$response = array();

			$result = (new Application_Model_News)->search(array(
				'latitude' => $latitude,
				'longitude' => $longitude,
				'radius' => $radius,
				'limit' => 15,
				'start' => $start
			), $user);

			if (count($result))
			{
				$commentTable = new Application_Model_Comments;
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$owner = $row->findDependentRowset('Application_Model_User')->current();

					$data = array(
						'id' => $row->id,
						'user_id' => $owner->id,
						'news' => $row->news,
						'created_date' => My_Time::time_ago($row->created_date),
						'updated_date' => $row->updated_date,
						'isdeleted' => $row->isdeleted,
						'isflag' => $row->isflag,
						'isblock' => $row->isblock,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'Address' => $row->Address,
						'score' => $row->score,
						'distance_from_source' => $row->distance_from_source,
						'comment_count' => $row->comment,
						'news_count' => $row->vote,
						'isLikedByUser' => $votingTable->findNewsLikeByUserId($row->id, $user->id) ? 'Yes' : 'No',
						'Name' => $owner->Name,
						'Profile_image' => $this->view->serverUrl() . $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
					);

					if ($row->image != null)
					{
						$data['images'] = $this->view->serverUrl() . $this->view->baseUrl('newsimages/' . $row->image);
					}

					$response['result'][] = $data;
				}
			}

			$response['status'] = 'SUCCESS';
			$response['message'] = 'Nearest point data rendered successfully';
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
	 * List user posts action.
	 *
	 * @return	void
	 */
	public function mypostsAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('Incorrect user id', -1);
			}

			$latitude = $this->_request->getPost('latitude');

			if (!is_numeric($latitude) || !My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value', -1);
			}

			$longitude = $this->_request->getPost('longitude');

			if (!is_numeric($longitude) || !My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value', -1);
			}

			$radius = $this->_request->getPost('radious', 0.8);

			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value', -1);
			}

			$start = $this->_request->getPost('fromPage', 0);

			if (!My_Validate::digit($start) || $start < 0)
			{
				throw new RuntimeException('Incorrect start value', -1);
			}

			$response = array();
			$filter = strtolower($this->_request->getPost('filter'));

			if ($filter == 'interest')
			{
				$response['interest'] = count($user->parseInterests());
			}

			$result = (new Application_Model_News)->search(array(
				'keywords' => $this->_request->getPost('searchText'),
				'latitude' => $latitude,
				'longitude' => $longitude,
				'radius' => $radius,
				'limit' => 15,
				'start' => $start,
				'filter' => $filter
			), $user);

			if (count($result))
			{
				$commentTable = new Application_Model_Comments;
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$owner = $row->findDependentRowset('Application_Model_User')->current();

					$data = array(
						'id' => $row->id,
						'user_id' => $owner->id,
						'news' => $row->news,
						'created_date' => My_Time::time_ago($row->created_date),
						'updated_date' => $row->updated_date,
						'isdeleted' => $row->isdeleted,
						'isflag' => $row->isflag,
						'isblock' => $row->isblock,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'Address' => $row->Address,
						'score' => $row->score,
						'distance_from_source' => $row->distance_from_source,
						'comment_count' => $row->comment,
						'news_count' => $row->vote,
						'isLikedByUser' => $votingTable->findNewsLikeByUserId($row->id, $user->id) ? 'Yes' : 'No',
						'Name' => $owner->Name,
						'Profile_image' => $this->view->serverUrl() . $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg'))
					);

					if ($row->image != null)
					{
						$data['images'] = $this->view->serverUrl() . $this->view->baseUrl('newsimages/' . $row->image);
					}

					$response['result'][] = $data;
				}
			}

			$response['status'] = 'SUCCESS';
			$response['message'] = 'Posts rendred successfully';
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
	 * List news comments action.
	 *
	 * @return	void
	 */
    public function getTotalCommentsAction()
	{
		try
		{
			if (!Application_Model_News::checkId($this->_request->getPost('news_id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Comments rendred successfully',
			);

			$page = $this->_request->getPost('offsetValue', 0);

			if (!My_Validate::digit($page) || $page < 0)
			{
				throw new RuntimeException('Incorrect offset value', -1);
			}

			$comments = (new Application_Model_Comments)->findAllByNewsId($news->id, 10, $page);

			if (count($comments))
			{
				foreach ($comments as $comment)
				{
					$comment_user = $comment->findDependentRowset('Application_Model_User')->current();

					$response['result'][] = array(
                        'id' => $comment->id,
                        'news_id' => $news->id,
                        'comment' => $comment->comment,
                        'user_name' => $comment_user->Name,
                        'user_id' => $comment_user->id,
                        'Profile_image' => $this->view->serverUrl() .
							$comment_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
                        'commTime' => $comment->created_at,
                        'totalComments' => $news->comment
					);
				}
			}
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
	 * Post news comment action.
	 *
	 * @return void
	 */
    public function postCommentAction()
	{
		try
		{
			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('Incorrect user ID', -1);
			}

			if (!Application_Model_News::checkId($this->_request->getPost('news_id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			$form = new Application_Form_Comment;

			if (!$form->isValid($this->_request->getPost()))
			{
				$this->_formValidateException($form);
			}

			$comment = (new Application_Model_Comments)->save($form, $news, $user);

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Comments Post Successfully',
				'result' => array(
					'id' => $comment->id,
					'news_id' => $news->id,
					'comment' => $comment->comment,
					'user_name' => $user->Name,
					'user_id' => $user->id,
					'Profile_image' => $this->view->serverUrl() . $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
					'commTime' => $comment->created_at,
					'totalComments' => $news->comment
				)
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
	 * Function to add like to a news.
	 * 
	 * @return void
	 */
	public function postLikeAction()
	{
		try
		{
			if (!Application_Model_News::checkId($this->_request->getPost('news_id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			if (!Application_Model_User::checkId($this->_request->getPost('user_id'), $user))
			{
				throw new RuntimeException('Incorrect user ID', -1);
			}

			$vote = $this->_request->getPost('vote');

			if ($vote != 1 && $vote != -1)
			{
				throw new RuntimeException('Incorrect vote value', -1);
			}

            $model = new Application_Model_Voting;

			if ($model->findNewsLikeByUserId($data['id'], $data['user_id']))
			{
				$response = array(
					'successalready' => 'registered already',
					'noofvotes_1' => $news->vote
				);
			}
			else
			{
				$model->saveVotingData($vote, $user->id, $news);

				$response = array(
					'news' => array(
						'id' => $news->id,
						'news_count' => $news->vote,
						'isLikedByUser' => 'Yes'
					),
					'success' => 'voted successfully',
					'noofvotes_2' => $news->vote
				);
			}
        }
		catch (Exception $e)
		{
			$response = array(
				'resonfailed' => 'Sorry unable to vote',
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
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
				$_errors[] = $error;
			}

			$errors[] = '"' . $field . '" - ' . implode(', ', $_errors);
		}

		throw new RuntimeException('Validate error: ' . implode(', ', $errors), -1);
	}
}
