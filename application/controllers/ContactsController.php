<?php

class ContactsController extends Zend_Controller_Action
{
	/**
	 * Facebook friends invite action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$user_invites = (new Application_Model_Invitestatus)->getData(array("user_id" => $user->id));

		if (!$user_invites || $user_invites->invite_count <= 0)
		{
			$this->_redirect($this->view->baseUrl('contacts/friends-list'));
		}

		$this->view->invite_count = $user_invites->invite_count;
		$this->view->hideRight = true;
		$this->view->currentPage = 'Message';

		$this->view->headScript()
			->appendFile($this->view->baseUrl('www/scripts/contactsindex.js?' . Zend_Registry::get('config_global')->mediaversion));
	}

	/**
	 * Invite by email action.
	 *
	 * @return void
	 */
	public function invitesAction() 
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$user_invites = (new Application_Model_Invitestatus)->getData(array("user_id" => $user->id));

		if (!$user_invites || $user_invites->invite_count <= 0)
		{
			$this->_redirect($this->view->baseUrl('contacts/friends-list'));
		}

		$invite_success = 0;

		if ($this->_request->isPost())
		{
			$emails = explode(",", $this->_request->getPost("emails"));

			if (!count($emails))
			{
				throw new RuntimeException('Emails cannot be blank', -1);
			}

			foreach ($emails as &$email)
			{
				$email = trim(strtolower($email));

				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					throw new RuntimeException('Incorrect email value: ' . var_export($email, true), -1);
				}
			}

			$emails = array_unique($emails);

			$newsFactory = new Application_Model_NewsFactory;
			$userTable = new Application_Model_User;
			$emailInvites = new Application_Model_Emailinvites;

			foreach (array_slice($emails, 0, $user_invites->invite_count) as $email)
			{
				$email_user = $userTable->findByEmail($email);

				if ($email_user)
				{
					My_Email::send(
						$email,
						'seearound.me connect request',
						array(
							'template' => 'invite-1',
							'assign' => array('user' => $user)
						)
					);

					My_Email::send(
						$user->Email_id,
						'User already registered',
						array(
							'template' => 'invite-2',
							'assign' => array('user' => $email_user)
						)
					);
				}
				else
				{
					$code = $newsFactory->generateCode();

					$emailInvites->insert(array(
						"sender_id" => $user->id,
						"receiver_email" => $email,
						"code" => $code,
						"created" => date('y-m-d H:i:s')
					));

					My_Email::send(
						$email,
						'seearound.me join request',
						array(
							'template' => 'invite-3',
							'assign' => array(
								'user' => $user,
								'code' => $code
							)
						)
					);

					$user_invites->invite_count--;
					$user_invites->save();
					$invite_success++;
				}
			}
		}

		$this->view->invite_success = $invite_success;
		$this->view->invite_count = $user_invites->invite_count;
		$this->view->hideRight = true;
		$this->view->currentPage = 'Message';
	}

	/**
	 * Ajax check facebook user status action.
	 *
	 * @return void
	 */
	public function checkfbstatusAction() 
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$network_id = trim($this->_request->getPost("network_id"));

			if ($network_id === '')
			{
				throw new RuntimeException('Network ID cannot be blank.', -1);
			}

			$response = array('status' => 1);

			$facebook_friends = Application_Model_Fbtempusers::findAllByNetworkId($network_id, $user->id);

			if ($facebook_friends)
			{
				$response['type'] = "facebook";
			}
			else
			{
				$facebook_user = Application_Model_User::findByNetworkId($network_id);

				if (count($facebook_user) > 0)
				{
					$response['address'] = $facebook_user->address();

					$friends = (new Application_Model_Friends)->getStatus($user->id, $facebook_user->id);

					if ($friends)
					{
						$response['data'] = array('status' => $friends->status);
						$response['type'] = "herespy";
					}
					else
					{
						$response['type'] = "follow";
					}
				}
				else
				{
					$response['type'] = "blank";
				}
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Ajax invite facebook user action.
	 *
	 * @return void
	 */
	public function inviteAction() 
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$user_invites = (new Application_Model_Invitestatus)->getData(array("user_id" => $user->id));

			if (!$user_invites || $user_invites->invite_count <= 0)
			{
				throw new RuntimeException('Sorry! you can not send this invitation', -1);
			}
			
			$network_id = trim($this->_request->getPost("network_id"));

			if ($network_id === '')
			{
				throw new RuntimeException('Network ID cannot be blank.', -1);
			}

			(new Application_Model_Fbtempusers)->invite(array(
				"sender_id" => $user->id,
				"reciever_nw_id" => $network_id,
			));

			$user_invites->invite_count--;
			$user_invites->save();

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Ajax follow facebook user action.
	 *
	 * @return void
	 */
	public function followAction() 
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$network_id = trim($this->_request->getPost("network_id"));

			if ($network_id === '')
			{
				throw new RuntimeException('Network ID cannot be blank.', -1);
			}

			$tableUser = new Application_Model_User;
			$facebook_user = $tableUser->findByNetworkId($network_id);

			if (!$facebook_user)
			{
				throw new RuntimeException('Incorrect network ID.', -1);
			}

			$reciever_email = $tableUser->recordForEmail($user->id, $facebook_user->id);

			My_Email::send(
				$reciever_email->recieverEmail,
				'seearound.me connect request',
				array(
					'template' => 'follow',
					'assign' => array('user' => $user)
				)
			);

			(new Application_Model_Friends)->invite(array(
				"reciever_id" => $facebook_user->id,
				"sender_id" => $user->id,
				"source" => "connect",
				"cdate" => date("Y-m-d H:i:s"),
				"udate" => date("Y-m-d H:i:s")
			));

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Friends list action.
	 *
	 * @return void
	 */
	public function friendsListAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$friends_count = (new Application_Model_Friends)->getCountByUserId($user->id, 1);

		$mediaversion = Zend_Registry::get('config_global')->mediaversion;

		$this->view->headLink()
			->appendStylesheet($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css'));

		$this->view->headScript()
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js'))
			->appendScript("	var friends_count = " . $friends_count . ";\n")
			->appendFile($this->view->baseUrl('www/scripts/friendlist.js?' . $mediaversion));

		$this->view->friendListExist = true;
		$this->view->friends_count = $friends_count;
	}

	/**
	 * Ajax load friends list action.
	 *
	 * @return void
	 */
	public function friendsListLoadAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$response = array('status' => 1);

			$tableFriends = new Application_Model_Friends;

			$offset = $this->_request->getPost("offset", 0);

			$friends = $tableFriends->findAllByUserId($user->id, 5, $offset);

			if (count($friends))
			{
				$tableUser = new Application_Model_User();

				foreach ($friends as $friend)
				{
					$_user = $friend->reciever_id == $user->id ?
						$friend->findDependentRowset('Application_Model_User', 'FriendSender')->current() :
						$friend->findDependentRowset('Application_Model_User', 'FriendReceiver')->current();

					$response['friends'][] = array(
						'id' => $_user->id,
						'image' => $_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'name' => $_user->Name,
						'address' => $_user->address(),
						'latitude' => $_user->lat(),
						'longitude' => $_user->lng()
					);
				}
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Friend action.
	 *
	 * @return void
	 */
	public function friendAction() 
	{
		try
		{
			$reciever_id = $this->_request->getPost('user');

			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $auth) || $reciever_id == $auth->id)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			if (!Application_Model_User::checkId($reciever_id, $reciever))
			{
				throw new RuntimeException('Incorrect reciever user ID', -1);
			}

			$action = $this->_request->getPost('action');

			$friendsModel = new Application_Model_Friends;
			$friend = $friendsModel->getStatus($auth->id, $reciever->id);

			if ($friend)
			{
				if ($action == 'reject')
				{
					$friend->status = 2;
					$friend->udate = date('Y-m-d H:i:s');
					$friend->save();
				}
				else
				{
					if ($action != 'confirm')
					{
						throw new RuntimeException('Incorrect action value', -1);
					}

					if ($friend->status == 2 && $friend->reciever_id != $auth->id)
					{
						throw new RuntimeException('Access denied', -1);
					}

					$friend->status = 1;
					$friend->udate = date('Y-m-d H:i:s');
					$friend->save();

					My_Email::send($reciever->Email_id, 'Friend approval', array(
						'template' => 'friend-approval',
						'assign' => array('name' => $auth->Name)
					));
				}
			}
			else
			{
				if ($action != 'add')
				{
					throw new RuntimeException('Incorrect action value', -1);
				}

				$friendsModel->createRow(array(
					'status' => 0,
					'sender_id' => $auth->id,
					'reciever_id' => $reciever_id,
					'source' => 'herespy',
					'cdate' => date('Y-m-d H:i:s'),
					'udate' => date('Y-m-d H:i:s')
				))->save();

				My_Email::send($reciever->Email_id, 'Friend invitation', array(
					'template' => 'friend-invitation',
					'assign' => array('name' => $auth->Name)
				));
			}

			$response = array('status' => 1);

			if ($this->_request->getPost('total'))
			{
				$response['total'] = $friendsModel->fetchRow(
					$friendsModel->select()
						->from($friendsModel, array('count(*) as friend_count'))
						->where('reciever_id =?', $auth->id)
						->where('status =?', 0)
				)->friend_count;
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Ajax user notifications action.
	 *
	 * @return void
	 */
	public function friendsNotificationAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', 401);
			}

			$response = array('status' => 1);

			$friends = (new Application_Model_Friends)->getCountByReceiverId($user->id, 0);

			if ($friends > 0)
			{
				$response['friends'] = $friends;
			}

			$messageModel = new Application_Model_Message;

			$result = $messageModel->fetchRow(
				$messageModel->publicSelect()
					->from($messageModel, array('count(*) as result_count'))
					->where('receiver_id =?', $user->id)
					->where('reciever_read =?', 'false')
					->orWhere('(reply_to =?', $user->id)
					->where('sender_read =?)', 'false')
			);

			$messages = $result ? $result->result_count : 0;

			if ($messages > 0)
			{
				$response['messages'] = $messages;
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => $e instanceof RuntimeException ?
					array('code' => $e->getCode(), 'message' => $e->getMessage()) :
					array('message' => 'Internal Server Error')
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Ajax load friend request list action.
	 *
	 * @return void
	 */
	public function requestsAction() 
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$response = array('status' => 1);

			$model = new Application_Model_Friends;
			$friends = $model->findAllByReceiverId($user->id, 0, 5);

			if ($count = count($friends))
			{
				$data = array();

				foreach ($friends as $friend)
				{
					$sender = $friend->findDependentRowset('Application_Model_User', 'FriendSender')->current();

					$data[] = array(
						'image' => $sender->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'name' => $sender->Name,
					);
				}

				$response['data'] = $data;
				$response['total'] = $count < 5 ? $count : $model->getCountByReceiverId($user->id, 0);
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Friend requests listing action.
	 *
	 * @return void
	 */
	public function allRequestsAction() 
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$this->view->data = (new Application_Model_Friends)->findAllByReceiverId($user->id, 0);
		$this->view->friendListExist = true;

		$this->view->headScript()
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places');
	}

	/**
	 * Ajax search user action.
	 *
	 * @return void
	 */
	public function searchAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$search = $this->_request->getParam('search', null);
			
			if (!strlen($search))
			{
				throw new RuntimeException('Incorrect search keyword', -1);
			}

			$response = array('status' => 1);

			$userModel = new Application_Model_User;
			$query = $userModel->select();

			if (strpos($search, '@') !== false)
			{
				$query->where("Email_id LIKE ?", '%' . $search . '%');
			}
			else
			{
				$query->where("Name LIKE ?", '%' . $search . '%');
			}

			$result = $userModel->fetchAll(
				$query
					->where('id !=?', $user->id)
					->order("Name")
			);

			if (count($result))
			{
				foreach ($result as $_user)
				{
					$response['result'][] = array(
						'id' => $_user->id,
						'name' => $_user->Name,
						'image' => $_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'address' => $_user->address()
					);
				}
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}
}
