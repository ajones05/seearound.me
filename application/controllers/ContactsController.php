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
			$this->_redirect($this->view->baseUrl('/'));
		}

		$user_invites = $user->findDependentRowset('Application_Model_Invitestatus')->current();

		if ($user_invites->invite_count <= 0)
		{
			$this->_redirect($this->view->baseUrl('contacts/friends-list'));
		}

		$this->view->invite_count = $user_invites->invite_count;
		$this->view->hideRight = true;

		$this->view->headScript()
			->appendFile(My_Layout::assetUrl('www/scripts/contactsindex.js', $this->view));
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
			$this->_redirect($this->view->baseUrl('/'));
		}

		$user_invites = $user->findDependentRowset('Application_Model_Invitestatus')->current();

		if ($user_invites->invite_count <= 0)
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
					$code = My_CommonUtils::generateCode();

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
				$address = $facebook_user->findDependentRowset('Application_Model_Address')->current();

				if (count($facebook_user) > 0)
				{
					$response['address'] = Application_Model_Address::format($address->toArray());

					$friends = (new Application_Model_Friends)->isFriend($user->id, $facebook_user->id);

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

			$user_invites = $user->findDependentRowset('Application_Model_Invitestatus')->current();

			if ($user_invites->invite_count <= 0)
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

			$friendsModel = new Application_Model_Friends;
			$friendStatus = $friendsModel->isFriend($user, $facebook_user);

			if (!$friendStatus)
			{
				$friendStatus = $friendsModel->createRow(array(
					'sender_id' => $user->id,
					'reciever_id' => $facebook_user->id,
					'status' => $friendsModel->status['confirmed'],
					'source' => 'connect'
				));
				$friendStatus->save();

				(new Application_Model_FriendLog)->insert(array(
					'friend_id' => $friendStatus->id,
					'user_id' => $user->id,
					'status_id' => $friendStatus->status
				));
			}

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
			$this->_redirect($this->view->baseUrl('/'));
		}

		$friends_count = (new Application_Model_Friends)->getCountByUserId($user->id, 1);

		$this->view->headLink()
			->appendStylesheet(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css', $this->view));

		$userAddress = $user->findDependentRowset('Application_Model_Address')->current();

		$this->view->headScript()
			->appendScript('var friends_count=' . $friends_count . ',' .
				'profileData=' . json_encode([
					'address' => Application_Model_Address::format($userAddress->toArray()),
					'latitude' => $userAddress->latitude,
					'longitude' => $userAddress->longitude
				]) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3&libraries=places')
			->appendFile(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js', $this->view))
			->appendFile(My_Layout::assetUrl('www/scripts/friendlist.js', $this->view));

        $this->view->homePageExist = true;
        $this->view->changeLocation = true;
		$this->view->displayMapSlider = true;
		$this->view->displayMapZoom = true;
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
					$address = $_user->findDependentRowset('Application_Model_Address')->current();

					$response['friends'][] = array(
						'id' => $_user->id,
						'image' => $_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'name' => $_user->Name,
						'address' => Application_Model_Address::format($address->toArray()),
						'latitude' => $address->latitude,
						'longitude' => $address->longitude
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
			$receiver_id = $this->_request->getPost('user');

			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $auth) || $receiver_id == $auth->id)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			if (!Application_Model_User::checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect receiver user ID', -1);
			}

			$friendsModel = new Application_Model_Friends;
			$friendStatus = $friendsModel->isFriend($auth, $receiver);

			switch ($this->_request->getPost('action'))
			{
				case 'follow':
					if ($friendStatus)
					{
						throw new RuntimeException('User already in friend list');
					}
					$friendStatus = $friendsModel->createRow(array(
						'sender_id' => $auth->id,
						'reciever_id' => $receiver->id,
						'status' => $friendsModel->status['confirmed'],
						'source' => 'herespy'
					));
					break;
				case 'reject':
					if (!$friendStatus)
					{
						throw new RuntimeException('User not found in friend list');
					}
					$friendStatus->status = $friendsModel->status['rejected'];
					break;
				default:
					throw new RuntimeException('Incorrect action', -1);
			}

			$friendStatus->save();

			(new Application_Model_FriendLog)->insert(array(
				'friend_id' => $friendStatus->id,
				'user_id' => $auth->id,
				'status_id' => $friendStatus->status
			));

			if ($friendStatus->status == $friendsModel->status['confirmed'])
			{
				My_Email::send($receiver->Email_id, 'New follower', array(
					'template' => 'friend-invitation',
					'assign' => array('name' => $auth->Name)
				));
			}

			$response = array('status' => 1);

			if ($this->_request->getPost('total'))
			{
				$response['total'] = $friendsModel->fetchRow(
					$friendsModel->select()
						->from($friendsModel, array('COUNT(*) as count'))
						->where('reciever_id=?', $auth->id)
						->where('status=' . $friendsModel->status['confirmed'])
						->where('notify=0')
				)->count;
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
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

			$friends = (new Application_Model_Friends)->getCountByReceiverId($user->id);

			if ($friends > 0)
			{
				$response['friends'] = $friends;
			}

			$messageModel = new Application_Model_ConversationMessage;

			$result = $messageModel->fetchRow(
				$messageModel->select()
					->from($messageModel, array('count' => 'count(*)'))
					->where('is_read=?', 0)
					->where('to_id=?', $user->id)
			);

			if ($result->count > 0)
			{
				$response['messages'] = $result->count;
			}
		}
		catch (Exception $e)
		{
			$response = array('status' => 0);

			if ($e instanceof RuntimeException)
			{
				$response['code'] = $e->getCode();
				$response['message'] = $e->getMessage();
			}
			else
			{
				$response['message'] = 'Internal Server Error';
			}
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

			$friends = $model->fetchAll(
				$model->select()
					->where('reciever_id=?', $user->id)
					->where('status=1')
					->where('notify=0')
					->limit(10)
			);

			if ($count = count($friends))
			{
				$data = array();

				foreach ($friends as $friend)
				{
					$sender = $friend->findDependentRowset('Application_Model_User', 'FriendSender')->current();

					$data[] = array(
						'image' => $sender->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'name' => $sender->Name,
						'link' => $this->view->baseUrl("home/profile/user/" . $sender->id)
					);
				}

				$model->update(
					array('notify' => 1),
					array('reciever_id=' . $user->id, 'status=1')
				);

				$response['data'] = $data;
				$response['total'] = $count < 5 ? $count : $model->getCountByReceiverId($user->id);
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
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
					$address = $_user->findDependentRowset('Application_Model_Address')->current();
					$response['result'][] = array(
						'id' => $_user->id,
						'name' => $_user->Name,
						'image' => $_user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						'address' => Application_Model_Address::format($address->toArray())
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
