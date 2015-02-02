<?php
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
		$writer = new Zend_Log_Writer_Stream(ROOT_PATH . '/log/mobile_api.log');
		$this->_logger = new Zend_Log($writer);
	}

	/**
	 * Authenticate user action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$response = array();

		try
		{
			$email = $this->_request->getParam('email');

			if (My_Validate::emptyString($email))
			{
				throw new RuntimeException('Email cannot be blank', -1);
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				throw new RuntimeException('Incorrect email address: ' . var_export($email, true), -1);
			}

			$pass = $this->_request->getParam('pass');

			if (My_Validate::emptyString($pass))
			{
				throw new RuntimeException('Password cannot be blank', -1);
			}

			$newsFactory = new Application_Model_NewsFactory();

			$return = $newsFactory->loginDetail(array('email' => $email), 'tocheck');

			if (!$return)
			{
				throw new RuntimeException('Incorrect user email', -1);
			}

			if ($return->id > 243)
			{
				$user = $newsFactory->loginDetail(array(
					'email' => $email,
					'pass' => hash('sha256', $pass),
				));
			}
			else
			{
				$user = $newsFactory->loginDetail(array(
					'email' => $email,
					'pass' => md5($pass),
				));
			}

			if (!$user)
			{
				throw new RuntimeException('Incorrect user email or password', -1);
			}

			$response = $user->toArray();
		}
		catch (Exception $e)
		{
			$response = 'NOT AUTHENTICATED';
		}

		$this->_logRequest($response);

		die(Zend_Json_Encoder::encode($response));
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
              echo(json_encode($response)); exit;
         } else {
              $response->status = "FAILED";
              if($type=='ALL'){ 
                $response->message="My Friend list could not be render";
              } else {
                $response->message="Individual Friend details could not be render";
              }
              $response->result = $result->toArray(); 
              echo(json_encode($response)); exit;
         }
    }
    
    public function getotheruserprofileAction()
    {
		$user_id = $this->_request->getParam('user_id');
		$other_user_id = $this->_request->getParam('other_user_id');

		$response = array(
			'message' => 'Profile Detail',
		);

		try
		{
			if ($other_user_id == null)
			{
				throw new RuntimeException('Parameter other_user_id cannot be blank', -1);
			}

			$userModel = new Application_Model_User();
			$other_user = $userModel->getUserProfile($other_user_id);

			if (!count($other_user))
			{
				throw new RuntimeException('Incorrect other_user_id id: ' . var_export($other_user_id, true), -1);
			}

			if ($user_id != null)
			{
				$user = $userModel->getUserProfile($user_id);

				if (!count($user))
				{
					throw new RuntimeException('Incorrect user_id id: ' . var_export($user, true), -1);
				}

				$friendsModel = new Application_Model_Friends();

				$result = $friendsModel->getStatus($user_id, $other_user_id);
				$response['friends'] = count($result) && $result->status == 1 ? 1 : 0;
			}

			$response['status'] = 'SUCCESS';
			$response['result'] = $other_user->toArray();
		}
		catch (Exception $e)
		{
			$response['status'] = 'FAILED';
		}

		die(Zend_Json::encode($response));
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
        if($_REQUEST) {
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
           
               /* ######## MAILING PART LEFT NEED TO BE IMPLEMENT ASAP
                  $this->view->name = $user_data->Name;
                  $this->view->message = "<p align='justify'> Your friend has sent you a message on HereSpy.<br><br><b>Subject:</b> " .$data['user']['subject']."<br><br><b>Message:</b> " .$data['user']['message']."<br><br>Please log in to HereSpy to reply to this message:".BASE_PATH." Please do not reply to this email</p>";
                  $this->view->adminName = "Admin";
                  $this->view->response = "Here Spy";
                
                  $this->to = $user_data->Email_id;
                  $this->from    = 'noreply@herespy.com:HerespyMessage';
                  $this->subject =  $data['user']['subject'];  
                  $this->message =  $data['user']['message']; 
                  $this->sendEmail($this->to, $this->from, $this->subject, $this->message); */
                  
               $result = $messageTable->saveData($data['user']);
               $response = new stdClass();
               if(isset($result)){
                 $response->status ="SUCCESS";
                 $response->message = "Message Send Successfully";
                 $response->result = $result->toArray(); 
                 echo(json_encode($response)); exit;
               } else {
                 $response->status = "FAILED";
                 $response->message="Message did not send Successfully";
                 $response->result = $result->toArray(); 
                 echo(json_encode($response)); exit;
             } 
       }
    }
    
    
     /**
      * Function to fetch list of message for user inbox
      * 
      * @return returns json encode response 
      */
    public function listmessageAction(){
        $messageTable = new Application_Model_Message();
        $newsFactory = new Application_Model_NewsFactory();
        $user_id = $_REQUEST['user_id'];
        //$user_id = 8;
        //$messageData = $messageTable->getUserData(array('receiver_id' => $user_id),true);
        $messageData = $messageTable->getUserInboxListData(array('receiver_id' => $user_id),true);
        $page = ($page) ? $page : 1;
          /*$paginator = Zend_Paginator::factory($messageData->toArray());
            $paginator->setCurrentPageNumber($page);
            $paginator->setItemCountPerPage(14); */
        $user_data    = $newsFactory->getUserData($user_id);
        $user_pro     = $newsFactory->getUserProfileData($user_id);
        $address_data = $newsFactory->getUserAddress($user_id);
        $response = new stdClass();
        if(isset($messageData)){
             $response->status ="SUCCESS";
             $response->message = "Message list Send Successfully";
             $response->result = $messageData->toArray(); 
             echo(json_encode($response)); exit;
           } else {
             $response->status = "FAILED";
             $response->message="Message list could not send Successfully";
             $response->result = $messageData->toArray(); 
             echo(json_encode($response)); exit;
         } 
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
             echo(json_encode($response)); exit;
           } else {
             $response->status = "FAILED";
             $response->message="Message list could not send Successfully";
             $response->result = $messageData->toArray(); 
             echo(json_encode($response)); exit;
         } 
             
    }
    
    
    /**
     * Function to retrieve message conversation between two users
     * 
     * *@return returns json encode response 
     */
    public function messageConversationAction(){
          $response = new stdClass();
          $messageTable = new Application_Model_Message();
          $newsFactory = new Application_Model_NewsFactory();
          $firstUserId    = $_REQUEST['user_id'];  //logined one
          $scondUserId    = $_REQUEST['other_user_id'];  // another
    
        if(isset($firstUserId)){
           $messageData = $messageTable->getConversationMessage(array('receiver_id' => $firstUserId,'sender_id' => $scondUserId),true);
           if(count($messageData) > 0){ 
              $response->status ="SUCCESS";
              $response->message = "Inbox Message between two user rendered Successfully";
              $response->result = $messageData->toArray(); 
              echo(json_encode($response)); exit;
           } else {
              $response->status = "FAILED";
              $response->message="seding inbox message between two user failed";
              $response->result = $messageData->toArray();
              echo(json_encode($response)); exit;
          }  
            
        }
    }
    
    

  public function notificationLoop($id,$user_id){
        if(isset($id)){
              $messageTable = new Application_Model_Message();
              $messageReplyTable = new Application_Model_MessageReply();
              $rowId  =  $id;
              $result =  $messageTable->viewed($rowId, $user_id);
              if($result){
                return true;
              } else {
                return false;
              }
        }
    }
   
    /**
     * Function to set notificatations status
     * 
     * *@return returns json encode response 
     */
    public function viewedAction() {
       // $idListArray = array();
        $response = new stdClass();
       //echo "<pre>"; print_r($_REQUEST); exit;
        $idArray      = $_REQUEST['post_id'];
        $user_id = $_REQUEST['user_id'];
        $idListArray = explode(",", $idArray);
        
        for ($i = 0; $i < count($idListArray); $i++) {
             $rowSet = $this->notificationLoop($idListArray[$i],$user_id);
         }


         if($rowSet){
               $response->status ="SUCCESS";
               $response->message = "Read Inbox Message Successfully";
               $response->result = "Read"; 
              echo(json_encode($response)); exit;
           } else {
               $response->status = "FAILED";
               $response->message="You did not read notifications sucessfully";
               $response->result = "Not Read";
               echo(json_encode($response)); exit;
          } 

    }


     /**
     * Function to set notificatations status
     * 
     * *@return returns json encode response 
     */
    public function setReadStatusAction() {
        $response = new stdClass();
        $postId      = $_REQUEST['message_id'];
        $user_id = $_REQUEST['user_id'];
        $result =  $messageTable->viewed($rowId, $user_id);
      
         if($result){
               $response->status  = "SUCCESS";
               $response->message = "Message set to read Successfully";
               $response->result  = "Read"; 
              echo(json_encode($response)); exit;
           } else {
               $response->status = "FAILED";
               $response->message= "Message did not set to read Successfully";
               $response->result = "Not Read";
               echo(json_encode($response)); exit;
          } 

    }
    
   
    /**
      * Function to save news data posted by user via Android Mobile OS
      * 
      * @return returns json encode response 
      */
     public function addmobinewsAction(){
         $newsFactory = new Application_Model_NewsFactory();
         $votingTable = new Application_Model_Voting();
         $newsTable   = new Application_Model_News();
         $userid = $this->_request->getParam('user_id');  
         $res = $this->_request->getParam('news'); 
         $lat = $this->_request->getParam('latitude');
         $lng = $this->_request->getParam('longitude');
         $address = $this->_request->getParam('address');
         $base = $this->_request->getParam('encodedImage');
         $id  = $newsFactory->addmobileNews($userid, $res, $lat, $lng, $address,$base);
         
         if ($id){
             $newsId = $id;
            if ($newsId) {
                $action = 'news';
                $action_id = $newsId;
                $votingTable = new Application_Model_Voting();
                $insert = $votingTable->firstNewsExistence($action, $action_id, $userid);
                if(isset($insert)){
                   $resonse ="POSTED";
                   echo(json_encode($resonse)); exit;
                } else {
                   $response ="NOT POSTED";   
                   echo(json_encode($resonse)); exit;
                }
            }
         }
      }
    
    
    /**
      * Function to save news data posted by user via I Phone Mobile OS
      * 
      * @return returns json encode response 
      */
     public function addimobinewsAction() {
         $newsFactory = new Application_Model_NewsFactory();
         $votingTable = new Application_Model_Voting();
         $newsTable   = new Application_Model_News();
         if ($_FILES) {
            if (getimagesize($_FILES['encodedImage']['tmp_name'])) {
                $name = $_FILES['encodedImage']['name'];
                $type = $_FILES['encodedImage']['type'];
                $tmp  = $_FILES['encodedImage']['tmp_name'];
                $size = $_FILES['encodedImage']['size'];
            }
        } else {
            $name = null;
            $type = null;
            $tmp  = null;
            $size = null;
        }  
          $userid  = $_REQUEST['user_id'];  
          $res     = $_REQUEST['news']; 
          $lat     = $_REQUEST['latitude'];
          $lng     = $_REQUEST['longitude'];
          $address = $_REQUEST['address'];
          $id = $newsFactory->addNews($userid, $res, $lat, $lng, $address, $name, $type, $tmp, $size);
        if ($id) {
             $newsId = $id;
             if ($newsId) {
                 $action = 'news';
                 $action_id = $newsId;
                 $votingTable = new Application_Model_Voting();
                 $insert = $votingTable->firstNewsExistence($action, $action_id, $userid);
                 $response = new stdClass();
                 if(isset($insert)){
                      $response->status ="SUCCESS";
                      $response->message = $res;
                      $response->userid = $userid;
                      echo(json_encode($response)); exit;
                 } else {
                       $response->status ="POSTING FAILED";  
                       $response->message = $res;
                       $response->userid = $userid; 
                      echo(json_encode($response)); exit;
                 }
             }
          }
     }
    
   
    
    /**
      * Function to update user profile
      * 
      * @return returns success or failed json encoded message.
      */
    public function editProfileAction() {   
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
            echo(json_encode($response)); exit;  
        } else {
           $response->status = "FAILED";
           $response->message = "Sorry,user profile did not updated";
           $response->result = $udatedUserdata; 
           echo(json_encode($response)); exit;    
        }
    }
    

      /**
      * Function to calculate time difference '
      * 
      * @return time difference 
      */
    public function time_ago_calculate ($tm, $rcs = 0) {
        $cur_tm = time(); 
        $dif = $cur_tm - strtotime($tm);
        $pds = array('second','minute','hour','day','week','month','year','decade');
        $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);

        for ($v = count($lngh) - 1; ($v >= 0) && (($no = $dif / $lngh[$v]) <= 1); $v--);
          if ($v < 0)
            $v = 0;
        $_tm = $cur_tm - ($dif % $lngh[$v]);

        $no = ($rcs ? floor($no) : round($no)); // if last denomination, round

        if ($no != 1)
          $pds[$v] .= 's ago';
        $x = $no . ' ' . $pds[$v];

        if (($rcs > 0) && ($v >= 1))
          $x .= ' ' . $this->time_ago($_tm, $rcs - 1);

        return $x;
    }


    
     /**
      * Function to find news near current user based on user's lat lng '
      * 
      * @return returns success or failed with result data set (json encoded message).
      */
    public function requestNearestAction() {
        $response  = new stdClass();
        $commentTable = new Application_Model_Comments();
        $userId    = $_REQUEST['userId'];
        $latitude  = $_REQUEST['latitude'];
        $longitude = $_REQUEST['longitude'];
        $radious   = $_REQUEST['radious'];
        $startPage = $_REQUEST['fromPage']; 
        $endPage   = $_REQUEST['endPage'];
        $searchTxt =''; 
       
        /* $userId    = 8; $latitude  = 28.449949482031496; $longitude = 77.49491019824222; $radious   = 1.1; $startPage = 0; $endPage   = 16;$searchTxt = ''; */
        //$this->view->userImage = Application_Model_User::getImage($userId);
        $newsFactory = new Application_Model_NewsFactory();
        $votingTable = new Application_Model_Voting();
        $commentTable = new Application_Model_Comments();
        if ($radious) {
             //$result = $this->findNearestPoint($latitude, $longitude, $radious, $searchTxt, null, $startPage, $endPage);
             $result = $this->findNearestPoint($latitude, $longitude, $radious, $searchTxt, null, $startPage, $endPage);
             foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =  $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                        // $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
             }
             
         } else {
             //$result = $this->findNearestPoint($latitude, $longitude, 1, $searchTxt, null, $startPage, $endPage);
             $result = $this->findNearestPoint($latitude, $longitude, 1, $searchTxt, null, $startPage, $endPage);
             foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =   $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                       //  $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
              }
            
         }
         
        $response = new stdClass();
        if(isset($result)){
            $response->status ="SUCCESS";
            $response->message = "Nearest point data rendered successfully";
            $response->result = $result; 
            echo(json_encode($response)); exit;
         } else {
            $response->status = "FAILED";
            $response->message="Nearest point data could not be render successfully";
            $response->result = $result; 
            echo(json_encode($response)); exit;
        }
    }
    

    /**
     * Function to retreive individual user posts
     * 
     * *@return returns json encode response 
     */
  public function mypostsAction(){
      $response   = new stdClass();
      //echo "<pre>"; print_r($_REQUEST); exit;
      $latitude   = $_REQUEST['latitude'];
      $longitude  = $_REQUEST['longitude'];
      $searchText = $_REQUEST['searchText'] ? $_REQUEST['searchText'] : null;
      $radious    = trim($_REQUEST['radious']) ? trim($_REQUEST['radious']) : '0.8';
      $fromPage   = $_REQUEST['fromPage'] ? $_REQUEST['fromPage'] : 0;
      $toPage     = $_REQUEST['endPage'] ? $_REQUEST['endPage'] : 16;
      $filter     = $_REQUEST['filter'];
      $userId     = $_REQUEST['user_id'];  
      
      
     /* $latitude   = 28.594908; $longitude = 77.320160; $searchText = ''; $radious    =  0.8;
        $fromPage   = 0;$toPage     = 16;$filter = 'All'; $userId = 8; */
      
      $newsFactory = new Application_Model_NewsFactory();
      $commentTable = new Application_Model_Comments();
      $votingTable = new Application_Model_Voting();
       if ($filter == 'All') {
            // $toPage = 500;
            //$result = $this->findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);
              $result = $this->findNearestPoint($latitude, $longitude, $radious, $searchText, null, $fromPage, $toPage);
              foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =   $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                       // $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
              }
        } else { if ($filter == 'Interest') {
                $interest = Application_Model_User::getUserInterest($userId);
                $interest = explode(",", $interest);
                $i = 0;
                $where = '(';
                foreach ($interest as $interestValue) {
                    if (($i > 0) && (trim($where) != '('))
                        $where .= ' or ';

                    if (trim($interestValue))
                       $where .= ' t1.news like "%' . trim($interestValue) . '%"  ';
                    $i++;
                }

                if (trim($where) != '(')
                    $where .= ')';
                else
                    $where = 'NULL';

                $response->interest = count($interest);
                $result = $this->findSearchingInterest($latitude, $longitude, $radious, $where, $userId, $fromPage, $toPage);
                    foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =   $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                       //  $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
                    }
                $resultVote = $this->findNewsVoting($userId, 0, 1000);
            } else if ($filter == 'Myconnection') {
                $result = $this->findMyConnectionMessage($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);
                    foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =   $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                       //  $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
                    }
                $resultVote = $this->findNewsVoting($userId, 0, 1000);
            } else if ($filter == 'Friends') {
                $result = $this->findSearchingMyFriends($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);
                    foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =   $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                       //  $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
                    }
                $resultVote = $this->findNewsVoting($userId, 0, 1000);
            } else {
                $result = $this->findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);
                    foreach($result as $index=>$resultSet){
                         $result[$index]['comment_count']  = $commentTable->getCommentCountOfNews($resultSet['id']);
                         $result[$index]['created_date'] =   $this->time_ago_calculate($resultSet['created_date']);
                         $result[$index]['distance_from_source'] =   $this->getPostDistance(trim($latitude),trim($longitude),trim($resultSet['latitude']),trim($resultSet['longitude']),"M");
                         $result[$index]['isLikedByUser'] =   $votingTable->isNewsLikedByUser($resultSet['id'],$userId);
                       //   $result[$index]['isCommentByUser'] =   $commentTable->getCommentsByUser($resultSet['id'],$userId);
                    }
                $resultVote = $this->findNewsVoting($userId , 0, 1000);
            }
        }
        
         if(isset($result)){
              $response->status ="SUCCESS";
              $response->message = "Posts rendred successfully";
              $response->result = $result;
              echo(json_encode($response)); exit;
         } else {
               $response->status ="FAILED";  
               $response->message = "Posts rendring failed";
               $response->result = $result; 
              echo(json_encode($response)); exit;
         }
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
              echo(json_encode($response)); exit;
         } else {
               $response->status ="FAILED";  
               $response->message = "Comments rendring failed";
               $response->result = $comments; 
              echo(json_encode($response)); exit;
         }
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
      /* $comments = 'comments test today1'; $newsId = 1510; $userId = 8; */
      
     /*  if (strpos($comments, "'") > 0 || strpos($comments, "<") > 0 || strpos($comments, ">") > 0) {
            $response->commentId = '';
            $response->image = '';
            die(Zend_Json_Encoder::encode($response));
        } */

        //$newsRow = $newsTable->getNewsWithDetails($newsId);
        $newsFactory = new Application_Model_NewsFactory();
        $id = $newsFactory->addComments($comments, $newsId, $userId);
        $commentRowSet = $newsFactory->viewTotalComments($newsId,-1,$userId);
         if(isset($id)){
		     /* $newstable   = new Application_Model_News();
              $votingTable = new Application_Model_Voting();
              $newsRow = $newstable->getNews(array('id' => $news_id));
              $response->noofvotes = $votingTable->getTotalVoteCounts('news',$newsId,$userId);
              $response->news = $newsRow->toArray(); */
              $response->status ="SUCCESS";
              $response->message = "Comments Post Successfully";
              $response->result = $commentRowSet;
           /* $response->userId =$userId;
              $response->postedAt =date("Y-m-d H:i:s");
              $response->image  =  Application_Model_User::getImage($userId);*/
              echo(json_encode($response)); exit;
         } else {
              $response->status  = "FAILED";  
              $response->message = "Comments Posting failed";
              $response->result  = $id; 
              $response->image  =  Application_Model_User::getImage($userId);
              echo(json_encode($response)); exit;
         }
     /*

      * Code to send email to every user who made comments @Mailing functionality will be implement ASAP
        
     */

      /*  if ($commentRows = $commentTable->getAllCommentUsers($newsId)) {
            //$newsRow = $newsTable->getNewsWithDetails($newsId);
            $userEmail = 'dinesh@successivesofwares.com';
            $userName = 'Dinesh Nagar';            
            $url = BASE_PATH . "info/news/nwid/" . $newsId;
            $this->from = $userEmail . ':' . $userName;
            $this->subject = "Herespy comment on your post";
            $message = $this->auth['user_name'] . " has commented on your post.<br><br>";
            if (strlen($comments) > 100) {
                $message .= nl2br(htmlspecialchars(substr($comments, 0, 100)));
            } else {
                $message .= nl2br(htmlspecialchars($comments)) . "<br>";
            }

            $message .= "<br><br>View the comments for this post: <a href='$url'>$url</a>";
            $this->view->message = "<p align='justify'>$message</p>";
            $this->view->adminPart = "no";
            $this->view->adminName = "Admin";
            $this->view->response = "Here Spy";
            $userArray = array();
            foreach ($commentRows as $row) {
                if ($row->user_id != $newsRow->user_id && $row->user_id != $userId) {
                    $userArray[] = $row->user_id;
                    $this->to = $row->email;
                    $this->view->name = $row->name;
                    $this->message = $this->view->action("index", "general", array());
                    $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                }
            }

            if (!in_array($newsRow->id, $userArray) && $newsRow->user_id != $userId) {
                $this->to = $newsRow->email;
                $this->view->name = $newsRow->name;
                $this->message = $this->view->action("index", "general", array());
                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
            }
        }
        die(Zend_Json_Encoder::encode($response));  */
    }
    
    /**
     * Function to add like to a news
     * 
     * *@return returns json encode response 
     */
    public function  postLikeAction(){
        $response = new stdClass();
        if ($_REQUEST) {
            $action	 = $_REQUEST['action'];
            $news_id = $_REQUEST['news_id'];
            $user_id = $_REQUEST['user_id'];
            $action = 'news';
            $data = $this->_request->getPost();
            $userTable = new Application_Model_User();
            $votingTable = new Application_Model_Voting();
            $row = $votingTable->saveVotingData($action, $news_id, $user_id);
            if ($row) {
                $response->successalready = 'registered already';
                $response->noofvotes_1 = $votingTable->getTotalVoteCounts($action, $news_id, $user_id);
                echo(json_encode($response)); exit; 
            } else {
                $newstable   = new Application_Model_News();
                $votingTable = new Application_Model_Voting();
                $newsRow = $newstable->getNews(array('id' => $news_id));
                $response->news = $newsRow->toArray();
                $response->success = 'voted successfully';
                $response->noofvotes_2 = $votingTable->getTotalVoteCounts($action, $news_id, $user_id);
                 /*Code for score measurement*/
                $score = $votingTable->measureLikeScore($action, $news_id, $user_id);
                echo(json_encode($response)); exit; 
            }
        } else {
             $response->resonfailed = 'Sorry unable to vote';
             echo(json_encode($response)); exit; 
        } 
    }
    
    
    /**
      * Function to call stored procedure to find nearest point based on user's lat,lng
      * 
      * @return returns resuls set data based on user's lat lng.
      */
    public function findNearestPoint($latitude, $longitude, $radious, $text, $only = null, $startPage = 0, $endPage = 16) {
        try {
            $db = Zend_Registry::getInstance()->get('db');
            if ($only) {
                $query = "CALL eventsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $text . "'," . $only . "," . $startPage . "," . $endPage . ")";
            } else {
                $query = "CALL eventsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $text . "',null," . $startPage . "," . $endPage . ")";
            }
            $stmt = $db->query($query, array(1));
            $returnArray = $stmt->fetchAll();
            $stmt->closeCursor();
            return($returnArray);
       
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
     }
     
    /**
     * Function to find nearest point
     * 
     * *@return returns json encode response 
     */
     public function findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId, $startPage = 0, $endPage = 16) {  
        try {
            $db = Zend_Registry::getInstance()->get('db');
            $db->beginTransaction();
              $query = "CALL searcheventsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "'," . $startPage . "," . $endPage . ")";
              //echo $query; exit;
              $stmt = $db->query($query, array(1));
              $returnArray = $stmt->fetchAll();
              $stmt->closeCursor();
              if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $db->closeConnection();
              }
            return($returnArray);
         } catch (Exception $e) {
             print_r($e->getMessage());
            exit;
        }
    }
    
    
    /**
     *  function for getting number of votes hits recieved by a particulaer news or post.
     * *@coded by: D
     * *@created:20-09-2013
     */
   function findNewsVoting($user_id, $startPage = 0, $endPage = 10000) {
      try {
            $db = Zend_Registry::getInstance()->get('db');
            if ($user_id) {
                $query = "CALL votingcount(" . $user_id . "," . $startPage . "," . $endPage . ")";
            } else {
                $query = "CALL votingcount(" . $user_id . "," . $startPage . "," . $endPage . ")";
            }

            $stmt = $db->query($query, array(1));
            $returnArray = $stmt->fetchAll();
            $stmt->closeCursor();
            return($returnArray);
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }
    
    /**
     * Function to find friends of user
     * 
     * *@return returns json encode response 
     */
    function findSearchingMyFriends($latitude, $longitude, $radious, $searchText, $userId, $startPage = 0, $endPage = 16) {
      
        try {
            $tableFriends = new Application_Model_Friends();
            $friendsList = $tableFriends->getTotalFriends($userId);
            $in = 'NULL';
            if ($friendsList) {
                foreach ($friendsList as $index => $list) {
                    if ($index == 0) {
                        $in = $list->id;
                        if ($in == '') {
                            $in = 0;
                        }
                     } else {
                        $in .= "," . $list->id;
                    }
                }
            }
            
            if ($searchText == '') {
                $searchText = 'NULL';
            }
        
            $db = Zend_Registry::getInstance()->get('db');
            $db->beginTransaction();
           

           /* if ($searchText == 'NULL' || $searchText == 'null') {
                 $query = "CALL searchfriendsnews_updated(" . $latitude . "," . $longitude . "," . $radious . ",null,'" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
               
             } else {
                 $query = "CALL searchfriendsnews_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
               
                 
            } */



            if ($searchText == 'NULL' || $searchText == 'null') {
                 $query = "CALL searchfriendsnews_updated(" . $latitude . "," . $longitude . "," . $radious . ",null,'" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
                 //$query = "CALL searchfriendsnews_1(" . $latitude . "," . $longitude . "," . $radious . ",null,'" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
             } else {
                 $query = "CALL searchfriendsnews_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
                 //$query = "CALL searchfriendsnews_1(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
                 
            }




            $stmt = $db->query($query, array(1));
            $returnArray = $stmt->fetchAll();
            $stmt->closeCursor();
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $db->closeConnection();
            }
            return($returnArray);
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }
    



    /**
     * Function to find friends of user
     * 
     * *@return returns json encode response 
     */
    function findMyConnectionMessage($latitude, $longitude, $radious, $searchText, $userId, $startPage = 0, $endPage = 16) {
        try {
            $in = $userId;
            if ($searchText == '') {
                $searchText = 'NULL';
            }
        
            $db = Zend_Registry::getInstance()->get('db');
            $db->beginTransaction();
          
            if ($searchText == 'NULL' || $searchText == 'null') {
                 $query = "CALL searchfriendsnews_updated(" . $latitude . "," . $longitude . "," . $radious . ",null,'" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
                 //$query = "CALL searchfriendsnews_1(" . $latitude . "," . $longitude . "," . $radious . ",null,'" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
             } else {
                 $query = "CALL searchfriendsnews_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
                 //$query = "CALL searchfriendsnews_1(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "','" . $in . "'," . $startPage . "," . $endPage . ")";
                 
            }


            $stmt = $db->query($query, array(1));
            $returnArray = $stmt->fetchAll();
            $stmt->closeCursor();
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $db->closeConnection();
            }
            return($returnArray);
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }


    /**
     * Function to find interest  of  a user
     * 
     * *@return returns json encode response 
     */
    public function findSearchingInterest($latitude, $longitude, $radious, $searchText, $userId, $startPage = 0, $endPage = 16) {
        try {
             $db = Zend_Registry::getInstance()->get('db');
             $db->beginTransaction();
             $query = "CALL searchinterestsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "'," . $startPage . "," . $endPage . ")"; //echo $query;
             $stmt = $db->query($query, array(1));
             $returnArray = $stmt->fetchAll();
             $stmt->closeCursor();

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
              $db->closeConnection();
            }

            return($returnArray);
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }

    /**
     * Function get distance of post from logined user location
     * 
     * *@return Distance between two LatLng
     *  @param int $latitudeFrom   Source latitude of logined user
     *  @param int $longitudeFrom  Source longitude of logined user
     *  @param int $latitudeTo     Post latitude of any user within defined circle
     *  @param int $longitudeTo    Post longitudeTo of any user within defined circle
     *  @return distance between two lat lng
     */
    function getPostDistance($lat1, $lon1, $lat2, $lon2, $unit) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
          return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
          } else {
              return $miles;
            }
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
