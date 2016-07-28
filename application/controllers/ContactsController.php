<?php
use Respect\Validation\Validator as v;

class ContactsController extends Zend_Controller_Action
{
	/**
	 * Facebook friends invite action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$user = Application_Model_User::getAuth();

		if ($user == null)
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
			->appendFile(My_Layout::assetUrl('www/scripts/contactsindex.js'));
	}

	/**
	 * Invite by email action.
	 *
	 * @return void
	 */
	public function invitesAction() 
	{
		$user = Application_Model_User::getAuth();

		if ($user == null)
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
			$settings = Application_Model_Setting::getInstance();

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
							'assign' => array('user' => $user),
							'settings' => $settings
						)
					);

					My_Email::send(
						$user->Email_id,
						'User already registered',
						array(
							'template' => 'invite-2',
							'assign' => array('user' => $email_user),
							'settings' => $settings
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
						'created' => new Zend_Db_Expr('NOW()')
					));

					My_Email::send(
						$email,
						'seearound.me join request',
						array(
							'template' => 'invite-3',
							'assign' => array(
								'user' => $user,
								'code' => $code
							),
							'settings' => $settings
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
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
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
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
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
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$network_id = trim($this->_request->getPost("network_id"));

			if (!v::stringType()->validate($network_id))
			{
				throw new RuntimeException('Incorrect Network ID value: ' .
					var_export($network_id, true));
			}

			$facebook_user = $userModel->findByNetworkId($network_id);

			if (!$facebook_user)
			{
				throw new RuntimeException('Incorrect network ID.', -1);
			}

			$reciever_email = $userModel->recordForEmail($user->id, $facebook_user->id);
			$settings = Application_Model_Setting::getInstance();

			My_Email::send(
				$reciever_email->recieverEmail,
				'seearound.me connect request',
				[
					'template' => 'follow',
					'assign' => ['user' => $user],
					'settings' => $settings
				]
			);

			$friendsModel = new Application_Model_Friends;
			$friendStatus = $friendsModel->isFriend($user, $facebook_user);

			if (!$friendStatus)
			{
				$friendsModel->createRow([
					'sender_id' => $user->id,
					'reciever_id' => $facebook_user->id,
					'status' => $friendsModel->status['confirmed'],
					'source' => 'connect'
				])->updateStatus($auth);
			}

			$response = ['status' => 1];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
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
		$user = Application_Model_User::getAuth();

		if ($user == null)
		{
			$this->_redirect($this->view->baseUrl('/'));
		}

		$settings = Application_Model_Setting::getInstance();

		$friends_count = (new Application_Model_Friends)->getCountByUserId($user->id, 1);

		$this->view->headLink()
			->appendStylesheet(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css'));

		$userAddress = $user->findDependentRowset('Application_Model_Address')->current();

		$config = Zend_Registry::get('config_global');
		$this->view->headScript()
			->appendScript('var friends_count=' . $friends_count . ',' .
				'profileData=' . json_encode([
					'address' => Application_Model_Address::format($userAddress->toArray()),
					'latitude' => $userAddress->latitude,
					'longitude' => $userAddress->longitude
				]) . ',' .
				'timizoneList=' . json_encode(My_CommonUtils::$timezone) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3&libraries=places&key=' .
				$settings['google_mapsKey'])
			->appendFile(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js'))
			->appendFile(My_Layout::assetUrl('www/scripts/friendlist.js'));

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
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$offset = $this->_request->getPost('offset', 0);

			if (!v::intVal()->validate($offset))
			{
				throw new RuntimeException('Incorrect offset value: ' .
					var_export($offset, true));
			}

			$friendModel = new Application_Model_Friends;
			$friends = $friendModel->findAllByUserId($user->id, 5, $offset);

			$response = ['status' => 1];

			if (count($friends))
			{
				foreach ($friends as $friend)
				{
					$friendUserId = $friend->reciever_id == $user->id ? $friend->sender_id : $friend->reciever_id;
					// TODO: merge
					$friendUser = Application_Model_User::findById($friendUserId);

					$response['friends'][] = [
						'id' => $friendUser->id,
						'image' => $this->view->baseUrl(
							Application_Model_User::getThumb($friendUser, '55x55')),
						'name' => $friendUser->Name,
						'address' => Application_Model_Address::format($friendUser),
						'latitude' => $friendUser->latitude,
						'longitude' => $friendUser->longitude
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
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
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$receiver_id = $this->_request->getPost('user');

			if (!v::intVal()->validate($receiver_id))
			{
				throw new RuntimeException('Incorrect receiver user ID value: ' .
					var_export($receiver_id, true));
			}

			if ($receiver_id == $user->id)
			{
				throw new RuntimeException('Access denied');
			}

			if (!$userModel->checkId($receiver_id, $receiver))
			{
				throw new RuntimeException('Incorrect receiver user ID');
			}

			$action = $this->_request->getPost('action');

			if (!v::stringType()->oneOf(v::equals('follow'),v::equals('reject'))->validate($action))
			{
				throw new RuntimeException('Incorrect action value: ' .
					var_export($action, true));
			}

			$total = $this->_request->getPost('total');

			if (!v::optional(v::intVal()->equals(1))->validate($total))
			{
				throw new RuntimeException('Incorrect total value: ' .
					var_export($total, true));
			}

			$model = new Application_Model_Friends;
			$friend = $model->isFriend($user, $receiver);

			switch ($action)
			{
				case 'follow':
					if ($friend)
					{
						throw new RuntimeException('User already in friend list');
					}

					$model->createRow([
						'sender_id' => $user->id,
						'reciever_id' => $receiver->id,
						'status' => $model->status['confirmed'],
						'source' => 'herespy'
					])->updateStatus($user);

					$settings = Application_Model_Setting::getInstance();

					My_Email::send($receiver->Email_id, 'New follower', [
						'template' => 'friend-invitation',
						'assign' => ['name' => $user->Name],
						'settings' => $settings
					]);
					break;
				case 'reject':
					if (!$friend)
					{
						throw new RuntimeException('User not found in friend list');
					}
					$friend->status = $model->status['rejected'];
					$friend->updateStatus($user);
					break;
			}

			$response = ['status' => 1];

			if ($total)
			{
				$response['total'] = $model->fetchRow(
					$model->select()
						->from($model, ['COUNT(*) as count'])
						->where('reciever_id=?', $user->id)
						->where('status=' . $model->status['confirmed'])
						->where('notify=0')
				)->count;
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
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
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
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
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$friendModel = new Application_Model_Friends;
			$friends = $friendModel->fetchAll(
				$friendModel->select()
					->where('reciever_id=?', $user->id)
					->where('status=1')
					->where('notify=0')
					->limit(10)
			);

			$response = ['status' => 1];
			$count = $friends->count();

			if ($count)
			{
				$data = [];

				foreach ($friends as $friend)
				{
					// TODO: merge
					$sender = Application_Model_User::findById($friend->sender_id);

					$data[] = [
						'name' => $sender->Name,
						'image' => $this->view->baseUrl(
							Application_Model_User::getThumb($sender, '55x55')),
						'link' => $this->view->baseUrl('home/profile/user/' .
							$friend->sender_id)
					];
				}

				$friendModel->update(
					['notify' => 1],
					['reciever_id=' . $user->id, 'status=1']
				);

				$response['data'] = $data;
				$response['total'] = $count < 5 ? $count : $friendModel->getCountByReceiverId($user->id);
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
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
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$keywords = $this->_request->getPost('keywords');

			if (!v::stringType()->validate($keywords))
			{
				throw new RuntimeException('Incorrect keywords value: ' .
					var_export($keywords, true));
			}

			$response = ['status' => 1];

			$query = $userModel->publicSelect()
				->where('u.id<>?', $user->id)
				->order('u.Name')
				->group('u.id');

			if (strpos($keywords, '@') !== false)
			{
				$query->where('u.Email_id LIKE ?', '%' . $keywords . '%');
			}
			else
			{
				$query->where('u.Name LIKE ?', '%' . $keywords . '%');
			}

			$users = $userModel->fetchAll($query);

			if (count($users))
			{
				foreach ($users as $_user)
				{
					$response['result'][] = [
						'id' => $_user->id,
						'name' => $_user->Name,
						'image' => $this->view->baseUrl(
							Application_Model_User::getThumb($_user, '55x55')),
						'address' => Application_Model_Address::format($_user)
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}
}
