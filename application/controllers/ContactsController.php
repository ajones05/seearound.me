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
			$this->_redirect('/');
		}

		if ($user['invite'] <= 0)
		{
			$this->_redirect('contacts/friends-list');
		}

		$this->view->invite_count = $user['invite'];
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
			$this->_redirect('/');
		}

		$userInvites = $user['invite'];

		if ($userInvites <= 0)
		{
			$this->_redirect('contacts/friends-list');
		}

		$userModel = new Application_Model_User;
		$settings = Application_Model_Setting::getInstance();
		$invite_success = 0;
		$sendInvites = 0;

		if ($this->_request->isPost())
		{
			$emails = $this->_request->getPost('emails');

			if (!v::stringType()->notEmpty()->validate($emails))
			{
				throw new RuntimeException('Incorrect emails value: ' .
					var_export($emails, true));
			}

			$inviteModel = new Application_Model_Emailinvites;
			$sendEmails = [];

			foreach (explode(',', $emails) as $email)
			{
				$email = trim(strtolower($email));

				if (!v::email()->validate($email))
				{
					throw new RuntimeException('Incorrect email address value: ' .
						var_export($email, true));
				}

				if (in_array($email, $sendEmails))
				{
					continue;
				}

				$inviteUser = $userModel->findByEmail($email);
				$sendEmails[] = $email;

				if ($inviteUser != null)
				{
					My_Email::send(
						$email,
						'seearound.me connect request',
						[
							'template' => 'invite-1',
							'assign' => ['user' => $user],
							'settings' => $settings
						]
					);

					My_Email::send(
						$user['Email_id'],
						'User already registered',
						[
							'template' => 'invite-2',
							'assign' => ['user' => $inviteUser],
							'settings' => $settings
						]
					);
				}
				else
				{
					$code = My_CommonUtils::generateCode();

					$inviteModel->insert([
						'sender_id' => $user['id'],
						'receiver_email' => $email,
						'code' => $code,
						'created' => new Zend_Db_Expr('NOW()')
					]);

					My_Email::send(
						$email,
						'seearound.me join request',
						[
							'template' => 'invite-3',
							'assign' => [
								'user' => $user,
								'code' => $code,
								'settings' => $settings
							],
							'settings' => $settings
						]
					);

					$sendInvites++;

					if (--$userInvites <= 0)
					{
						break;
					}
				}
			}
		}

		$userModel->updateWithCache([
			'invite' => $userInvites
		], $user);

		$this->view->invite_success = $sendInvites;
		$this->view->invite_count = $userInvites;
		$this->view->settings = $settings;
		$this->view->hideRight = true;
	}

	/**
	 * Ajax check facebook user status action.
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

			$facebook_friends = Application_Model_Fbtempusers::findAllByNetworkId(
				$network_id, $user['id']);

			if ($facebook_friends)
			{
				$response['type'] = "facebook";
			}
			else
			{
				$facebookUser = Application_Model_User::findByNetworkId($network_id);

				if ($facebookUser != null)
				{
					$response['address'] = Application_Model_Address::format($facebookUser);

					$friends = (new Application_Model_Friends)
						->isFriend($user['id'], $facebookUser->id);

					if ($friends)
					{
						$response['data'] = ['status' => $friends->status];
						$response['type'] = 'herespy';
					}
					else
					{
						$response['type'] = 'follow';
					}
				}
				else
				{
					$response['type'] = 'blank';
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

			if ($user['invite'] <= 0)
			{
				throw new RuntimeException('Sorry! you can not send this invitation');
			}

			$network_id = trim($this->_request->getPost('network_id'));

			if (!v::stringType()->validate($network_id))
			{
				throw new RuntimeException('Incorrect network id value: ' .
					var_export($network_id, true));
			}

			$fbUserModel = new Application_Model_Fbtempusers;
			$result = $fbUserModel->findAllByNetworkId($network_id, $user['id']);

			if ($result == null)
			{
				$fbUserModel->insert([
					'sender_id' => $user['id'],
					'reciever_nw_id' => $network_id,
				]);

				(new Application_Model_User)->updateWithCache([
					'invite' => $user['invite']-1
				], $user);
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

			$network_id = trim($this->_request->getPost('network_id'));

			if (!v::stringType()->validate($network_id))
			{
				throw new RuntimeException('Incorrect Network ID value: ' .
					var_export($network_id, true));
			}

			$facebookUser = $userModel->findByNetworkId($network_id);

			if ($facebookUser == null)
			{
				throw new RuntimeException('Incorrect Network ID: ' .
					var_export($network_id, true));
			}

			$friendModel = new Application_Model_Friends;
			$friendStatus = $friendModel->isFriend($user, $facebookUser);

			if (!$friendStatus)
			{
				$friendId = $friendModel->insert([
					'sender_id' => $user['id'],
					'receiver_id' => $facebookUser['id'],
					'status' => $friendModel->status['confirmed'],
					'source' => 'connect'
				]);

				(new Application_Model_FriendLog)->insert([
					'friend_id' => $friendId,
					'user_id' => $user['id'],
					'status_id' => $friendModel->status['confirmed']
				]);
			}

			My_Email::send(
				$user['Email_id'],
				'seearound.me connect request',
				[
					'template' => 'follow',
					'assign' => ['user' => $user]
				]
			);

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
	 */
	public function friendsListAction()
	{
		$user = Application_Model_User::getAuth();

		if ($user == null)
		{
			$this->_helper->flashMessenger('Please log in to access this page.');
			$this->_redirect('/');
		}

		$this->view->layout()->setLayout('map');
		$this->view->viewPage = 'community';
		$this->view->searchForm = new Application_Form_PostSearch;
		$this->view->user = $user;

		$this->view->appendScript = ['opts=' . json_encode([
			'lat' => $user['latitude'],
			'lng' => $user['longitude'],
			'filter' => [2]
		]) . ',user=' . json_encode([
			'name' => $user['Name'],
			'image' => $this->view->baseUrl(
				Application_Model_User::getThumb($user, '55x55')),
			'location' => [$user['latitude'], $user['longitude']],
			'is_admin' => $user['is_admin']
		]) . ',timizoneList=' . json_encode(My_CommonUtils::$timezone)];


		$friendModel = new Application_Model_Friends;
		$this->view->friends = $friendModel->findAllByUserId($user, [
			'limit' => 30,
			'address' => true
		]);

		$postModel = new Application_Model_News;
		$posts = $postModel->search([
			'latitude' => $user['latitude'],
			'longitude' => $user['longitude'],
			'radius' => 1.5,
			'filter' => [2],
			'start' => 0,
			'limit' => 15
		], ['auth' => $user]);

		if ($posts->count())
		{
			$data = [];

			foreach ($posts as $post)
			{
				$data[$post['id']] = [
					'user_id' => $post['user_id'],
					'cid' => $post['category_id'],
					'lat' => $post['latitude'],
					'lng' => $post['longitude']
				];
			}

			$this->view->appendScript[] = 'postData=' . json_encode($data);
		}
	}

	/**
	 * Ajax load friends list action.
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

			$start = $this->_request->getPost('start', 0);

			if (!v::intVal()->validate($start))
			{
				throw new RuntimeException('Incorrect start value: ' .
					var_export($start, true));
			}

			$friends = (new Application_Model_Friends)->findAllByUserId($user, [
				'limit' => 30,
				'offset' => $start,
				'address' => true
			]);

			$response = ['status' => 1];

			if ($friends->count() > 0)
			{
				foreach ($friends as $friend)
				{
					$response['data'][] = $this->view->partial('contacts/_friend.html', [
						'friend' => $friend,
						'alias' => $friend['receiver_id'] == $user['id'] ?
							'sender_' : 'receiver_'
					]);
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

			if ($receiver_id == $user['id'])
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

			$friendModel = new Application_Model_Friends;
			$friend = $friendModel->isFriend($user, $receiver);

			switch ($action)
			{
				case 'follow':
					if ($friend)
					{
						throw new RuntimeException('User already in friend list');
					}

					$friendId = $friendModel->insert([
						'sender_id' => $user['id'],
						'receiver_id' => $receiver['id'],
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
						'assign' => ['name' => $user['Name']]
					]);
					break;
				case 'reject':
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
					break;
			}

			$response = ['status' => 1];

			if ($total)
			{
				$response['total'] = $friendModel->fetchRow(
					$friendModel->select()
						->from($friendModel, ['COUNT(*) as count'])
						->where('receiver_id=?', $user['id'])
						->where('status=' . $friendModel->status['confirmed'])
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

			$friends = (new Application_Model_Friends)->getCountByReceiverId($user['id']);

			if ($friends > 0)
			{
				$response['friends'] = $friends;
			}

			$messageModel = new Application_Model_ConversationMessage;

			$result = $messageModel->fetchRow(
				$messageModel->select()
					->from($messageModel, array('count' => 'count(*)'))
					->where('is_read=?', 0)
					->where('to_id=?', $user['id'])
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
					->where('receiver_id=?', $user['id'])
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
						'link' => $this->view->baseUrl('profile/' . $friend->sender_id)
					];
				}

				$friendModel->update(
					['notify' => 1],
					'receiver_id=' . $user['id'], 'status=1'
				);

				$response['data'] = $data;
				$response['total'] = $count < 5 ? $count :
					$friendModel->getCountByReceiverId($user['id']);
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
				->where('u.id<>?', $user['id'])
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

			if ($users->count())
			{
				foreach ($users as $user)
				{
					$response['data'][] = [
						'id' => $user['id'],
						'name' => $user['Name'],
						'html' => $this->view->partial('contacts/_user-autocomplete.html',
							['user' => $user])
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => true || $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
		}

		$this->_helper->json($response);
	}
}
