<?php

class ContactsController extends Zend_Controller_Action
{
    public function indexAction()
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $inviteStatus = new Application_Model_Invitestatus();
        if($inviteStatus = $inviteStatus->getData(array('user_id' => $user->id))) {
            if($inviteStatus->invite_count <= 0) {
                $this->_redirect($this->view->baseUrl('contacts/friends-list'));
            } else {
                $this->view->inviteStatus = $inviteStatus;
            }
        }
        $this->view->hideRight = true;
    }
    
    public function invitesAction() 
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$config = Zend_Registry::get('config_global');
        $this->view->hideRight = true;
        $inviteStatus = new Application_Model_Invitestatus();
        $newsFactory = new Application_Model_NewsFactory();
        $emailInvites = new Application_Model_Emailinvites();
        $userTable = new Application_Model_User();
        if($inviteStatusData = $inviteStatus->getData(array("user_id" => $user->id))) {
            if($inviteStatusData->invite_count <= 0) {
                $this->_redirect($this->view->baseUrl('contacts/friends-list'));
            } else {
                $this->view->inviteStatus = $inviteStatusData;
            }
        }
        if($this->_request->isPost()) {
            $emails = $this->_request->getPost("emails", null);
            $emailMessage = $this->_request->getPost("messageText", null);
            $emails = explode(",", $emails);
            $total = (($inviteStatusData->invite_count) >= count($emails))?(count($emails)):($inviteStatusData->invite_count); 

            $alreadyUser = 0;
            for ($i=1; $i <= $total; $i++) {
                if($userRow = $userTable->getUsers(array('Email_id' => trim($emails[$i-1])))) {
                    /*
                     * Email to invited user 
                     */
					My_Email::send(
						$emails[$i-1],
						'seearound.me connect request',
						array(
							'template' => 'invite-1',
							'assign' => array('user' => $user)
						)
					);

                    /*
                     * Email to login user 
                     */
					My_Email::send(
						$user->Email_id,
						'User already registered',
						array(
							'template' => 'invite-2',
							'assign' => array(
								'user' => $userRow,
								'email' => $emails[$i-1]
							)
						)
					);

                    $alreadyUser++;
                } else {
                    $data = array(
                        "sender_id" => $user->id,
                        "receiver_email" => trim($emails[$i-1]),
                        "code" => $newsFactory->generateCode(),
                        "created" => date('y-m-d H:i:s')
                    );

                    $row[] = $emailInvites->createRow($data)->save();

					My_Email::send(
						$emails[$i-1],
						'seearound.me join request',
						array(
							'template' => 'invite-3',
							'assign' => array(
								'user' => $user,
								'code' => $data['code'],
								'message' => $emailMessage
							)
						)
					);

                    $inviteStatusData->invite_count = $inviteStatusData->invite_count-1;
                    $inviteStatusData->save();  
                }
            }
            $this->view->inviteStatus = $inviteStatus->getData(array("user_id"=>$user->id));
            $this->view->success = count($row);
            $this->view->already = $alreadyUser;
        }
        
    }

    public function checkfbstatusAction() 
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $response = new stdClass();
        $tableUser = new Application_Model_User;
        $tableFriends = new Application_Model_Friends;
        $tableFbFriends = new Application_Model_Fbtempusers;
        $tableAddress = new Application_Model_Address;
        if($this->_request->isPost()) {
            $nwId = $this->_request->getPost("network_id", null);
            $data = array(
                "Network_id" => $nwId
            );
            $resultRow = $tableUser->getUsers($data);
            if(count($resultRow) > 0) {
                $select = $tableFbFriends->select()
                    ->where("reciever_nw_id =?", $resultRow->Network_id)
                    ->where("sender_id =?", $user->id);
                if($fbFriebds = $tableFbFriends->fetchRow($select)) {
                    $response->data = $fbFriebds->toArray();
                    $response->count = count($fbFriebds);
                    $response->type = "facebook";
                } else {
                    $select = $tableAddress->select()
                            ->where("user_id =?", $resultRow->id);
                    if($addressRow = $tableAddress->fetchRow($select)) {
                        $response->address = $addressRow->toArray();
                    }
                    $select = $tableFriends->select()
                        ->where("friends.sender_id = ".$user->id." AND friends.reciever_id = ".$resultRow->id)
                        ->orWhere("friends.sender_id = ".$resultRow->id." AND friends.reciever_id = ".$user->id);
                    if($friends = $tableFriends->fetchRow($select)) {                        
                        $response->data = $friends->toArray();
                        $response->count = count($fbFriebds);
                        $response->type = "herespy";
                    } else {
                        $response->data = $resultRow->toArray();
                        $response->count = 0;
                        $response->type = "follow";
                    }
                }
            }else {
                $select = $tableFbFriends->select()
                    ->where("reciever_nw_id =?", $nwId)
                    ->where("sender_id =?", $user->id);
                if($fbFriebds = $tableFbFriends->fetchRow($select)) {
                    $response->data = $fbFriebds->toArray();
                    $response->count = count($fbFriebds);
                    $response->type = "facebook";
                } else {
                    $response->count = 0;
                    $response->type = "blank";
                }
                
            }
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->response = $response;
        }
    }
    
    public function inviteAction() 
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $response = new stdClass();
        $tableUser = new Application_Model_User(); 
        $tableFbFriends = new Application_Model_Fbtempusers(); 
        $inviteStatus = new Application_Model_Invitestatus();
        if($this->_request->isPost()) {
            if($inviteRow = $inviteStatus->getData(array("user_id" => $user->id))) {
                if($inviteRow->invite_count > 0) {
                    $data = array(
                        "sender_id" => $user->id,
                        "reciever_nw_id" => $this->_request->getPost("network_id", null),
                        "full_name" => $this->_request->getPost("name", null)
                    );
                    $resultData = $tableFbFriends->invite($data);
                    if(count($resultData) > 0) {
                        $response->data = $resultData;
                    }
                    $inviteRow->invite_count = $inviteRow->invite_count-1;
                    $inviteRow->save();
                } else {
                    $response->errors = "Sorry! you can not send this invitation";
                }
            } else {
                $response->errors = "Sorry! you can not send this invitation";
            }
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->data = $resultRows;
        }
    }
    
    public function followAction() 
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $response = new stdClass();
        $tableUser = new Application_Model_User;
        $tableFriends = new Application_Model_Friends;
        if($this->_request->isPost()) {
            $nwId = $this->_request->getPost("network_id", null);
            $reciewerRow = $tableUser->getUsers(array("Network_id"=>$nwId));
            $mailValues = $tableUser->recordForEmail($user->id, $reciewerRow->id);

			My_Email::send(
				$mailValues->recieverEmail,
				'seearound.me connect request',
				array(
					'template' => 'follow',
					'assign' => array('user' => $user)
				)
			);

            $resultData = $tableFriends->invite(array(
                "reciever_id" => $reciewerRow->id,
                "sender_id" => $user->id,
                "source" => "connect",
                "cdate" => date("Y-m-d H:i:s"),
                "udate" => date("Y-m-d H:i:s")
			));

            if(count($resultData) > 0) {
                $resultData['reciever_nw_id'] = $nwId;
            }

            $response->data = $resultData;
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->data = $resultRows;
        }
    }
    
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

		die(Zend_Json_Encoder::encode($response));
	}

    public function makeFriendFbAction() 
    {
        $response = new stdClass();
        $tableUser = new Application_Model_User;
        $tableFriends = new Application_Model_Friends;
        $tableFbFriends = new Application_Model_Fbtempusers;
        if($this->_request->isPost()) {
            $sender = $this->_request->getPost("sender", 0);
            $reciever = $this->_request->getPost("reciever", 0);
            $action = $this->_request->getPost("action", null);
            $select = $tableFbFriends->select()
                ->where("sender_id =?", $sender)
                ->where("reciever_nw_id =?", $reciever);
            if($row = $tableFbFriends->fetchRow($select)) { 
                $select = $tableUser->select()
                    ->where("Network_id =?", $row->reciever_nw_id);
                if($userRow = $tableUser->fetchRow($select)) {
                    if($action == "friend"){
                        $db = $tableUser->getAdapter();
                        $db->beginTransaction();
                        try{
                            $select = $tableFriends->select()
                                ->where("sender_id = ".$row->sender_id." AND reciever_id = ".$userRow->id)
                                ->orWhere("sender_id = ".$userRow->id." AND reciever_id = ".$row->sender_id);
                            $friendRows = $tableFriends->fetchAll($select);
                            if(count($friendRows) > 0) { 
                                foreach($friendRows as $friendRow) {
                                    $friendRow->status = 1;
                                    $friendRow->udate = date("Y-m-d H:i:s");
                                    $friendRow->save();
                                }
                                $row->delete();
                            }else { 
                                $data = array(
                                    "sender_id" => $row->sender_id,
                                    "reciever_id" => $userRow->id,
                                    "status" => 1,
                                    "source" => "facebook",
                                    "cdate" => date("Y-m-d H:i:s"),
                                    "udate" => date("Y-m-d H:i:s")
                                );
                                $friendRows = $tableFriends->createRow($data);
                                $friendRows->save();
                                $row->delete();
                            }
                            $response->done = "yes";
                            $response->type = "friend";
                            $response->data = $friendRows->toArray();
                            $db->commit();
                            $db->closeConnection();
                        } catch (Exception $e) {
                            die($e);
                        }
                    }else if($action == "unfriend") {
                        $userRow->status = 2;
                        $userRow->save();
                        $response->done = "yes";
                        $response->type = "unfriend";
                        $response->data = $row->toArray();
                    }else if($action == "delete") {
                        if(count($userRow) > 0) {
                            $userRow->delete();
                        }
                        if(count($row) > 0) {
                            $row->delete();
                        }
                        $response->done = "yes";
                        $response->type = "delete";
                    }
                }
            }
        }
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
        }else {
            $this->view->response = $response;
        }   
    }
    
    public function friendsNotificationAction()
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        /*
         * Making instance of models class 
         */
        $response = new stdClass();
        $tableFriends = new Application_Model_Friends();
        $messageTable = new Application_Model_Message();
        if($this->_request->isPost()) {  
            $data = array(
                'reciever_id' => $user->id,
                'status' => '0'
            );
            $friendRow = $tableFriends->getFriends($data, true);
            $msgRow    = $messageTable->getNoteMessage($user->id);
            if($friendRow) {
                $friendRow = $friendRow->toArray();
            }
            if($msgRow) {
                $msgRow = $msgRow->toArray();
            }
            $response->data = $friendRow;
            $response->total = count($friendRow);
            $response->msg = $msgRow;
            $response->msgTotal = count($msgRow);
            $response->totalFriends = $tableFriends->getCountByUserId($user->id, 1);
        }
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function requestsAction() 
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		try
		{
			$response = array('status' => 1);

			$model = new Application_Model_Friends;
			$friends = $model->findAllByReceiverId($user->id, 0, 5);
			$friends_count = count($friends);

			if ($friends_count)
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
				$response['total'] = $friends_count < 5 ? $friends_count : $model->getCountByReceiverId($user->id, 0);
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
