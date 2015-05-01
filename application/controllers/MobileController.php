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

			$pass = $this->_request->getPost('password');

			if (My_Validate::emptyString($pass))
			{
				throw new RuntimeException('Password cannot be blank', -1);
			}

			$newsFactory = new Application_Model_NewsFactory;

			$user = $newsFactory->loginDetail(array(
				'email' => $email,
				'pass' => hash('sha256', $pass),
			));

			if (!$user)
			{
				throw new RuntimeException('Incorrect user email or password', -1);
			}

			if ($user->Status != 'active')
			{
				throw new RuntimeException('User is not active', -1);
			}

			$newsFactory->updateToken(md5(uniqid($user->Email_id, true)), $user->id);

			$loginStatus = new Application_Model_Loginstatus;

			$loginRow = $loginStatus->setData(array(
				'user_id' => $user->id,
				'login_time' => date('Y-m-d H:i:s'),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			));

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
					$inviteStatusRow->updated = date('Y-m-d H:i:s');
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
					'Old_email' => $user->Old_email,
					'Password' => $user->Password,
					'Birth_date' => $user->Birth_date,
					'Creation_date' => $user->Creation_date,
					'Update_date' => $user->Update_date,
					'Profile_image' => $user->Profile_image,
					'Status' => $user->Status,
					'Network_id' => $user->Network_id,
					'Conf_code' => $user->Conf_code,
					'is_admin' => $user->is_admin,
					'Token' => $user->Token,
					'address' => $user->address,
					'latitude' => $user->latitude,
					'longitude' => $user->longitude,
					'Activities' => $user->Activities,
					'Gender' => $user->Gender,
					'login_id' => $loginRow->id,
				)
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 'FAILED',
				'message' => 'NOT AUTHENTICATED',
				'result' => ''
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

			$status_model = new Application_Model_Loginstatus;

			$loginRow = $status_model->setData(array(
				'user_id' => $user->id,
				'login_time' => new Zend_Db_Expr('NOW()'),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			));

			// TODO: ???
			if (date('D') == 'Mon')
			{
				$loginRows = $status_model->sevenDaysOldData($user->id);
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
					// TODO: add user data
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
      * Function to retreive friend list '
      * 
      * @return returns success or failed with result data set (json encoded message).
      */
      
    public function myfriendlistAction() 
    {
         $newsFactory  = new Application_Model_NewsFactory();
         $tableUser    = new Application_Model_User();
         $tableFriends = new Application_Model_Friends();
         $inviteStatus = new Application_Model_Invitestatus();
         $userId    = $_REQUEST['user_id'];
         $targetFriendId = $_REQUEST['friend_id'];
         $type = $_REQUEST['type'];
       
          /* $userId = 8;
           $targetFriendId = 3;
           $type = 'ALL1'; */ 

         if(isset($userId) && $userId !=''){
            if($type=='ALL'){
               $result = $tableFriends->getTotalFriendsListWs(trim($userId)); 
            } else {
               $result = $tableFriends->getIndividualFriendsWs(trim($userId),$targetFriendId); 
            }
         }
         
         $response = new stdClass();
        
         if(isset($result)){
              $response->status ="SUCCESS";
              if($type=='ALL'){
                $response->message = "My Friend list rendered successfully";
              } else {
                $response->message = "Individual Friend details rendered successfully";
              }
              $response->result = $result->toArray(); 
         } else {
              $response->status = "FAILED";
              if($type=='ALL'){ 
                $response->message="My Friend list could not be render";
              } else {
                $response->message="Individual Friend details could not be render";
              }
              $response->result = $result->toArray(); 
         }

		$this->_logRequest($response);

		$this->_helper->json($response);
    }
    
    public function getotheruserprofileAction()
    {
		$response = array(
			'message' => 'Profile Detail',
		);

		try
		{
			$other_user_id = $this->_request->getParam('other_user_id');

			if (!Application_Model_User::checkId($other_user_id, $other_user))
			{
				throw new RuntimeException('Incorrect other_user_id id: ' . var_export($other_user_id, true), -1);
			}

			$user_id = $this->_request->getParam('user_id');

			if ($user_id != null)
			{
				if (!Application_Model_User::checkId($user_id, $user))
				{
					throw new RuntimeException('Incorrect user_id id: ' . var_export($user_id, true), -1);
				}

				$result = Application_Model_Friends::getInstance()->getStatus($user_id, $other_user_id);

				$response['friends'] = count($result) && $result->status == 1 ? 1 : 0;
			}

			$response['status'] = 'SUCCESS';
			$response['result'] = $other_user->toArray();
		}
		catch (Exception $e)
		{
			$response['status'] = 'FAILED';
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
    }

    
   
    /**
      * Function to sending message to friend
      * 
      * @return returns json encode response 
      */
    public function sendmessageAction(){
         $message    = $_REQUEST['message'];
         $userId     = $_REQUEST['subject'];
         $senderId   = $_REQUEST['sender_id'];
         $recieverId = $_REQUEST['reciever_id']; 
         
       /* $message    = "Hello this is new message";
          $subject    = "Regrading Message";
          $senderId   = 8;
          $recieverId = 144; */
        
        $response = new stdClass();
        $data = array();
        $errors = array();

            $messageTable = new Application_Model_Message(); 
            $newsFactory = new Application_Model_NewsFactory();
              
              	$data['user']['sender_id']   = $senderId;
                $data['user']['receiver_id'] = $recieverId;
                $data['user']['created']     = date('Y-m-d H:i:s');
                $data['user']['updated']     = date('Y-m-d H:i:s');
                $data['user']['is_read']     = 'false';
                $data['user']['is_deleted']  = 'false';
                $data['user']['is_valid']    = 'true';
                $data['user']['subject']     = $subject;
                $data['user']['message']     = $message;
               
                $user_data = $newsFactory->getUserData($data['user']['receiver_id']);
                  
               $result = $messageTable->saveData($data['user']);
               $response = new stdClass();
               if(isset($result)){
                 $response->status ="SUCCESS";
                 $response->message = "Message Send Successfully";
                 $response->result = $result->toArray(); 
               } else {
                 $response->status = "FAILED";
                 $response->message="Message did not send Successfully";
                 $response->result = $result->toArray(); 
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

			// TODO: auth

			$model = new Application_Model_Message;

			$messages = $model->fetchAll(
				$model->publicSelect()
					->where('message.receiver_id =?', $user->id)
					->order('updated DESC')
					// TODO: limit
					// TODO: limit start
			);

			$result = array();

			foreach ($messages as $message)
			{
				$user = $message->findDependentRowset('Application_Model_User', 'Receiver')->current();

				$result[] = array(
					'id' => $message->id,
					'sender_id' => $message->sender_id,
					'subject' => $message->subject,
					'message' => $message->message,
					'created' => $message->created,
					'updated' => $message->updated,
					'reciever_read' => $message->reciever_read,
					'Name' => $user->Name,
					'Email_id' => $user->Email_id,
					'Profile_image' => $user->getProfileImage(BASE_PATH . 'www/images/img-prof40x40.jpg')
				);
			}

			$response = array(
				'status' => 'SUCCESS',
				// TODO: ???
				'message' => 'Message list Send Successfully',
				'result' => $result
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
     * Function to fetch list of unread message for user inbox
     * 
     * *@return returns json encode response 
     */
    public function unreadmessagesAction(){
        $messageTable = new Application_Model_Message();
        $newsFactory = new Application_Model_NewsFactory();
        $user_id = $_REQUEST['user_id'];
        //$user_id = 8;
        $messageData = $messageTable->getUnreadUserMessage(array('receiver_id' => $user_id),true);
        $response = new stdClass();
        if(isset($messageData)){
             $response->status ="SUCCESS";
             $response->message = "Message list Send Successfully";
             $response->result = $messageData->toArray(); 
           } else {
             $response->status = "FAILED";
             $response->message="Message list could not send Successfully";
             $response->result = $messageData->toArray(); 
         } 

		$this->_logRequest($response);

		$this->_helper->json($response);
    }

    /**
	 * TODO: test
     * Function to retrieve message conversation between two users
     * 
     * *@return returns json encode response 
     */
    public function messageConversationAction()
	{
		$firstUserId = $this->_request->getPost('user_id');
		$scondUserId = $this->_request->getPost('other_user_id');

		try
		{
			// TODO: validate users
			if (!$firstUserId || !$scondUserId)
			{
				throw new RuntimeException('Incorrect user id', -1);
			}

			$messageData = (new Application_Model_Message)->getConversationMessage(array(
				'receiver_id' => $firstUserId,
				'sender_id' => $scondUserId
			), true);

			if(!count($messageData))
			{
				// TODO: ???
				throw new RuntimeException('Seding inbox message failed', -1);
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => 'Inbox Message between two user rendered Successfully',
				'result' => $messageData->toArray()
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 'FAILED',
				'message' => 'seding inbox message between two user failed'
			);
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
    }

    /**
	 * TODO: test
     * Function to set notificatations status
     * 
     * *@return returns json encode response 
     */
	public function viewedAction()
	{
        $response = new stdClass();
        $idArray      = $_REQUEST['post_id'];
        $user_id = $_REQUEST['user_id'];
        $idListArray = explode(",", $idArray);
		$messageTable = new Application_Model_Message();

        for ($i = 0; $i < count($idListArray); $i++) {
             $rowSet = $messageTable->viewed($idListArray[$i], $user_id);
         }

         if($rowSet){
               $response->status ="SUCCESS";
               $response->message = "Read Inbox Message Successfully";
               $response->result = "Read"; 
           } else {
               $response->status = "FAILED";
               $response->message="You did not read notifications sucessfully";
               $response->result = "Not Read";
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
			$data = $this->_request->getPost();

			// TODO: validate mobile app user authentication

			if (!Application_Model_User::checkId(My_ArrayHelper::getProp($data, 'user_id'), $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$form = new Application_Form_News;

			if (!$form->isValid($data))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$model = new Application_Model_News;

			$data = $form->getValues();
			$data['news_html'] = My_CommonUtils::renderHtml($data['news'], empty($data['images']));
			$data['id'] = $model->insert($data);

			if (!Application_Model_Voting::getInstance()->firstNewsExistence('news', $data['id'], $user->id))
			{
				throw new RuntimeException('Save voting error', -1);
			}

			$response = array(
				'status' => 'SUCCESS',
				'message' => $data['news'],
				'userid' => $data['user_id']
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
      * Function to update user profile
      * 
      * @return returns success or failed json encoded message.
      */
    public function editProfileAction()
	{
        $newsFactory  = new Application_Model_NewsFactory();
        $userTable    = new Application_Model_User;
        $profileTable = new Application_Model_Profile;
        $addressTable = new Application_Model_Address;
        $userId = trim($_REQUEST['user_id']);
        $successFlag;
        $udatedUserdata = array();              
        $getUserDataRowSet = $newsFactory->getUser(array("user_data.id" =>$userId));
     
        if($_REQUEST){
          /*$dobDate         = mysql_real_escape_string($_REQUEST['DOB']);
          $timestamp       = strtotime($dobDate);
          $dobDateFormated = date('Y-m-d', $timestamp); */
          $dobDateFormated = $_REQUEST['DOB'];
         
          $udata = array(
                'Name' => $_REQUEST['Name'],
                'Birth_date' => $dobDateFormated,
                'Email_id'   => $_REQUEST['Email_id']
          );
           
          $pdata = array(
              'public_profile' => ($_REQUEST['allow']) ? 1 : 0,
              'Activities' => $_REQUEST['Activityes'],
              'Gender' => $_REQUEST['Gender']
          );
        }
        
        /* Start Image Uploading */
         if ($_FILES['encodedImage']['name']) {
                 $url = urldecode($newsFactory->wsimageUpload($_FILES['encodedImage']['name'], $_FILES['encodedImage']['size'], $_FILES['encodedImage']['tmp_name'], $userId));
         }
        /* End image Uploading */
        
        $db = $userTable->getDefaultAdapter();
        $db->beginTransaction();
        try {
            $userTable->update($udata, $userTable->getAdapter()->quoteInto("id =?", $userId));
            if ($prow = $profileTable->fetchRow($profileTable->select()->where("user_id =?", $userId))) {
                        $profileTable->update($pdata, $profileTable->getAdapter()->quoteInto("user_id =?", $userId));
            } else {
                $pdata['user_id'] = $userId;
                $prow = $profileTable->createRow($pdata);
                $prow->save();
            }

            $db->commit();
            $returnvalue = $newsFactory->getUser(array("user_data.id" => $userId));
            $udatedUserdata['user_id'] = $returnvalue->id;
            $udatedUserdata['is_fb_login'] = false;
            $udatedUserdata['Name'] = $returnvalue->Name;
            $udatedUserdata['Email_id'] = $returnvalue->Email_id;
            $udatedUserdata['latitude'] = $returnvalue->latitude;
            $udatedUserdata['longitude'] = $returnvalue->longitude;
            $udatedUserdata['Profile_image'] = $returnvalue->Profile_image;
            $udatedUserdata['address'] = $returnvalue->address;
            $udatedUserdata['Gender'] = $returnvalue->Gender;
            $udatedUserdata['Activities'] = $returnvalue->Activities;
            $udatedUserdata['Birth_date'] = $returnvalue->Birth_date;
            $successFlag = 1;
        } catch (Exception $e) {
           $db->rollBack();
           $udatedUserdata = "No Data";
           echo "User Profile Updating Failed"; exit; 
        }
    
        if(isset($successFlag)) {
            $response->status = "SUCCESS";
            $response->message = "User profile has been updated successfully";
            $response->result = $udatedUserdata; 
        } else {
           $response->status = "FAILED";
           $response->message = "Sorry,user profile did not updated";
           $response->result = $udatedUserdata; 
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
		$response = array(
			// TODO: remove
			'result' => array()
		);

		try
		{
			$user_id = $this->_request->getPost('userId');

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' . var_export($user_id, true), -1);
			}

			$latitude = $this->_request->getPost('latitude');

			if (My_Validate::emptyString($latitude))
			{
				throw new RuntimeException('Latitude cannot be blank', -1);
			}

			if (!My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value: ' . var_export($latitude, true), -1);
			}

			$longitude = $this->_request->getPost('longitude');

			if (My_Validate::emptyString($longitude))
			{
				throw new RuntimeException('Longitude cannot be blank', -1);
			}

			if (!My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value: ' . var_export($longitude, true), -1);
			}

			$radius = $this->_request->getPost('radious', 1);

			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value: ' . var_export($radius, true), -1);
			}

			$fromPage = $this->_request->getPost('fromPage', 0);

			if (!My_Validate::digit($fromPage) || $fromPage < 0)
			{
				throw new RuntimeException('Incorrect fromPage value: ' . var_export($fromPage, true), -1);
			}

			$result = Application_Model_News::getInstance()->findByLocation($latitude, $longitude, $radius, 15, $fromPage);

			if (count($result))
			{
				$commentTable = new Application_Model_Comments;
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$response['result'][] = array(
						'id' => $row->id,
						'user_id' => $row->user_id,
						'news' => $row->news,
						'images' => $row->images,
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
						'comment_count' => $commentTable->getCountByNewsId($row->id),
						'isLikedByUser' => $votingTable->isNewsLikedByUser($row->id, $user->id) ? 'Yes' : 'No',
					);
				}
			}

			$response['status'] = 'SUCCESS';
			$response['message'] = 'Nearest point data rendered successfully';
		}
		catch (Exception $e)
		{
			$response['status'] = 'FAILED';
			$response['message'] = 'Nearest point data could not be render successfully';
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
		$response = array(
			// TODO: remove
			'result' => array()
		);

		try
		{
			$user_id = $this->_request->getPost('user_id');

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Incorrect user id: ' . var_export($user_id, true), -1);
			}

			$latitude = $this->_request->getPost('latitude');

			if (My_Validate::emptyString($latitude))
			{
				throw new RuntimeException('Latitude cannot be blank', -1);
			}

			if (!My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value: ' . var_export($latitude, true), -1);
			}

			$longitude = $this->_request->getPost('longitude');

			if (My_Validate::emptyString($longitude))
			{
				throw new RuntimeException('Longitude cannot be blank', -1);
			}

			if (!My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value: ' . var_export($longitude, true), -1);
			}

			$radius = $this->_request->getPost('radious', 0.8);

			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value: ' . var_export($radius, true), -1);
			}

			$fromPage = $this->_request->getPost('fromPage', 0);

			if (!My_Validate::digit($fromPage) || $fromPage < 0)
			{
				throw new RuntimeException('Incorrect fromPage value: ' . var_export($fromPage, true), -1);
			}

			$newsTable = new Application_Model_News;
			$select = $newsTable->select();

			$keywords = $this->_request->getPost('searchText');

			if (!My_Validate::emptyString($keywords))
			{
				$select->where('news LIKE ?', '%' . $keywords . '%');
			}

			$filter = $this->_request->getPost('filter');

			switch ($filter)
			{
				case 'Interest':
					$interests = $user->parseInterests();
					$response['interest'] = count($interests);
					$result = $newsTable->findByLocationAndInterests($latitude, $longitude, $radius, 15, $fromPage, $interests, $select);
					break;
				case 'Myconnection':
					$result = $newsTable->findByLocationAndUser($latitude, $longitude, $radius, 15, $fromPage, $user, $select);
					break;
				case 'Friends':
					$result = $newsTable->findByLocationInFriends($latitude, $longitude, $radius, 15, $fromPage, $user, $select);
					break;
				default:
					$result = $newsTable->findByLocation($latitude, $longitude, $radius, 15, $fromPage, $select);
					break;
			}

			if (count($result))
			{
				$commentTable = new Application_Model_Comments;
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$response['result'][] = array(
						'id' => $row->id,
						'user_id' => $row->user_id,
						'news' => $row->news,
						'images' => $row->images,
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
						'comment_count' => $commentTable->getCountByNewsId($row->id),
						'isLikedByUser' => $votingTable->isNewsLikedByUser($row->id, $user->id) ? 'Yes' : 'No',
					);
				}
			}

			$response['status'] = 'SUCCESS';
			$response['message'] = 'Posts rendred successfully';
		}
		catch (Exception $e)
		{
			$response['status'] = 'FAILED';
			$response['message'] = 'Posts rendring failed';
		}

		$this->_logRequest($response);

		$this->_helper->json($response);
	}

    /**
     * Function to retreive total comments posted on a news
     * 
     * *@return returns json encode response 
     */
    public function getTotalCommentsAction(){
        $response = new stdClass();
        $newsId =  $_REQUEST['news_id'];  
        $offsetValue  =  $_REQUEST['offsetValue']; 
      
         $newsFactory = new Application_Model_NewsFactory();
         $comments = $newsFactory->viewTotalComments($newsId,$offsetValue);
         if(isset($comments)){
              $response->status ="SUCCESS";
              $response->message = "Comments rendred successfully";
              $response->result = $comments;
              $response->nextpage = ++$limit;
         } else {
               $response->status ="FAILED";  
               $response->message = "Comments rendring failed";
               $response->result = $comments; 
         }

		$this->_logRequest($response);

		$this->_helper->json($response);
    }
    
    
    /**
     * Function to post comment on a news
     * 
     * *@return returns json encode response 
     */
    public function postCommentAction(){
        $commentTable = new Application_Model_Comments();
        $newsTable    = new Application_Model_News();
        $newsFactory = new Application_Model_NewsFactory();
        $response = new stdClass();
        $comments = $_REQUEST['comments'];
        $newsId   = $_REQUEST['news_id'];
        $userId   = $_REQUEST['user_id'];  

        $newsFactory = new Application_Model_NewsFactory();
        $id = $newsFactory->addComments($comments, $newsId, $userId);
        $commentRowSet = $newsFactory->viewTotalComments($newsId,-1,$userId);
         if(isset($id)){
              $response->status ="SUCCESS";
              $response->message = "Comments Post Successfully";
              $response->result = $commentRowSet;
         } else {
              $response->status  = "FAILED";  
              $response->message = "Comments Posting failed";
              $response->result  = $id; 
              $response->image  =  Application_Model_User::getImage($userId);
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

            $model = new Application_Model_Voting;
			$vote_count = $model->getTotalVoteCounts('news', $news->id, $user->id);

			if ($model->saveVotingData('news', $news->id, $user->id))
			{
				$response = array(
					'successalready' => 'registered already',
					'noofvotes_1' => $vote_count
				);
			}
			else
			{
				$response = array(
					'news' => $news->toArray(),
					'success' => 'voted successfully',
					'noofvotes_2' => $vote_count
				);

				$model->measureLikeScore('news', $news->id, $user->id);
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
}
