<?php

class HomeController extends My_Controller_Action_Herespy {

    public function init() {
          /* Initialize action controller here */
         $this->view->changeLocation = false;
    }

    public function indexAction() {
        $this->view->homePageExist = true;
        $this->view->changeLocation = true;

        if ($this->auth['latitude'] == "" && $this->auth['longitude'] == "") {
            $this->_redirect(BASE_PATH . 'home/edit-profile');
        }

		$this->view->headScript()->prependFile('/www/scripts/publicNews.js');
    }
    
     public function managedbAction(){
         
        $newsFactory = new Application_Model_NewsFactory();
        $userTable   = new Application_Model_User; 
        $newsTable   = new Application_Model_News; 
        $responseToken = $newsTable->manipulateDb();  
     
     }

    public function editProfileAction() {
        $this->view->myeditprofileExist = true;
        $this->view->changeLocation = true;
        $this->view->viewAllPost = true;
        $newsFactory = new Application_Model_NewsFactory();
        $userTable = new Application_Model_User;
        $profileTable = new Application_Model_Profile;
        $addressTable = new Application_Model_Address;
        $returnUrl = $this->_request->getParam("url", '');
        $this->view->user_data = $user_data = $newsFactory->getUser(array("user_data.id" => $this->auth['user_id']));
        if ($this->_request->isPost()) {
            $errors = array();
            $data = array();
            $userTable->validateData($this->request, $data, $errors);
            if ($user_data->Email_id == $this->_request->getPost("Email_id")) {
                unset($errors['Email_id']);
                unset($data['Email_id']);
            }
            if (empty($errors)) {
                $dob = $this->_request->getPost("yeardropdown") . "-" . $this->_request->getPost("monthdropdown") . "-" . $this->_request->getPost("daydropdown");
                if ((strstr($dob, "Year")) || (strstr($dob, "Month")) || (strstr($dob, "Day"))) {
                    $udata = array(
                        'Name' => $this->_request->getPost("Name")
                    );
                } else {

                    $udata = array(
                        'Name' => $this->_request->getPost("Name"),
                        'Birth_date' => $dob
                    );
                }

                $pdata = array(
                    'public_profile' => ($this->_request->getPost("allow")) ? 1 : 0,
                    'Activities' => $this->_request->getPost("Activityes"),
                    'Gender' => $this->_request->getPost("Gender")
                );

                $adata = array(
                    'address' => $this->_request->getPost("Location"),
                    'latitude' => $this->_request->getPost("RLatitude"),
                    'longitude' => $this->_request->getPost("RLongitude")
                );

                $db = $userTable->getDefaultAdapter();
                $db->beginTransaction();

                try {
                  $userTable->update($udata, $userTable->getAdapter()->quoteInto("id =?", $this->auth['user_id']));
                    if ($prow = $profileTable->fetchRow($profileTable->select()->where("user_id =?", $this->auth['user_id']))) {
                        $profileTable->update($pdata, $profileTable->getAdapter()->quoteInto("user_id =?", $this->auth['user_id']));
                    } else {
                        $pdata['user_id'] = $this->auth['user_id'];
                        $prow = $profileTable->createRow($pdata);
                        $prow->save();
                    }

                    if ($arow = $addressTable->fetchRow($addressTable->select()->where("user_id =?", $this->auth['user_id']))) {

                        $addressTable->update($adata, $addressTable->getAdapter()->quoteInto("user_id =?", $this->auth['user_id']));
                    } else {

                        $adata['user_id'] = $this->auth['user_id'];

                        $arow = $addressTable->createRow($adata);

                        $arow->save();
                    }

                    $db->commit();

                    $auths = Zend_Auth::getInstance();

                    $returnvalue = $newsFactory->getUser(array("user_data.id" => $this->auth['user_id']));

                    $authData['user_id'] = $returnvalue->id;

                    $authData['is_fb_login'] = false;

                    $authData['user_name'] = $returnvalue->Name;

                    $authData['user_email'] = $returnvalue->Email_id;

                    $authData['latitude'] = $returnvalue->latitude;

                    $authData['longitude'] = $returnvalue->longitude;

                    $authData['pro_image'] = $returnvalue->Profile_image;

                    $authData['address'] = $returnvalue->address;

                    $auths->getStorage()->write($authData);
                } catch (Exception $e) {

                    $db->rollBack();

                    $this->view->errors = $e;
                }

                if ($returnUrl != "") {

                    $this->_redirect($returnUrl);
                } else {

                    $this->_redirect(BASE_PATH . "home/profile");
                }
            } else {

                $this->view->errors = $errors;
            }
        }
    }

    public function imageUploadAction(){
        $response = new stdClass();
        $newsFactory = new Application_Model_NewsFactory();
        if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
            $url = urldecode($newsFactory->imageUpload($_FILES['ImageFile']['name'], $_FILES['ImageFile']['size'], $_FILES['ImageFile']['tmp_name'], $this->auth['user_id']));
            $response->url = BASE_PATH . $url;
            $auth = Zend_Auth::getInstance();
            $returnvalue = $newsFactory->getUser(array("user_data.id" => $this->auth['user_id']));
            $authData['user_id'] = $returnvalue->id;
            $authData['user_name'] = $returnvalue->Name;
            $authData['latitude'] = $returnvalue->latitude;
            $authData['longitude'] = $returnvalue->longitude;
            $authData['pro_image'] = $returnvalue->Profile_image;
            $authData['address'] = $returnvalue->address;
            $auth->getStorage()->write($authData);
        }

        die(Zend_Json_Encoder::encode($response));
    }

    public function profileAction() {
        //echo "<pre>"; print_r( $this->_request->getParam('user')); exit;
        //echo "<pre>"; print_r($this->auth['user_id']); exit;
        $this->view->currentPage = 'Profile';
        $this->view->myprofileExist = true;
        $this->view->reciever = $user_id = $this->_request->getParam('user', $this->auth['user_id']);
        //echo $user_id; exit;
        $this->view->returnUrl = BASE_PATH . 'home/profile/user/' . $user_id;
        $profileTable = new Application_Model_Profile;
        $prow = $profileTable->fetchRow($profileTable->select()->where("user_id =?", $user_id));
        $this->view->profile = $prow->public_profile;
        if ($user_id && is_finite($user_id)) {
            $newsFactory = new Application_Model_NewsFactory();
             $user_data =  $newsFactory->getUser(array("user_data.id" => $user_id));
            
             $latestPost = $newsFactory->getLatestPost($user_id);
        
             $counter = 0;
             
                if(isset($latestPost) && $latestPost!=''){
                 foreach($latestPost as $post){
                   if($counter<1){
                      $getPostNews  =  $post['news'];
                      $this->view->latestPost = $getPostNews;
                      $counter++;
                     }
                   }
                } else {
                     $this->view->latestPost= "N/A";
               }
              
            if ($user_data->latitude == "" && $user_data->longitude == "") {
                $this->_redirect(BASE_PATH . 'home/edit-profile');
            }

            $tableFriends = new Application_Model_Friends;

            if (isset($this->auth['user_id'])) {
                $this->view->friendStatus = $tableFriends->getStatus($this->auth['user_id'], $user_id);
            }

            if (count($user_data)) {
                $this->view->user_data = $user_data;
            } else {
                $this->_redirect(BASE_PATH);
            }
          } else {
            $this->_redirect(BASE_PATH);
        }
    }

    public function addNewsAction() {
        $this->view->myeditprofileExist = true;
        $response = new stdClass();
        $this->_helper->layout()->disableLayout();
        
        $newsFactory = new Application_Model_NewsFactory();
        $votingTable = new Application_Model_Voting();
        $newsTable = new Application_Model_News();

        $userid = $this->auth['user_id'];
        $res    = $this->_getParam('news');
        $lat    = $this->_getParam('latitude');
        $lng    = $this->_getParam('longitude');
 
        if (strpos($res, "<") > 0 || strpos($res, ">") > 0) {
            $response->news = array();
            $response->id = "";
            $response->image = "";
            $response->lastRowId = '';
            $response->result = array();
            die(Zend_Json_Encoder::encode($response));
          }
        $address = $this->_getParam('address');  //echo "<pre>"; print_r($_REQUEST);  print_r($_FILES); exit;
       
        if ($_FILES['Filedata']) {
            if (getimagesize($_FILES['Filedata']['tmp_name'])) {
                $name = $_FILES['Filedata']['name'];
                $type = $_FILES['Filedata']['type'];
                $tmp  = $_FILES['Filedata']['tmp_name'];
                $size = $_FILES['Filedata']['size'];
            }
        } else {
            $name = null;
            $type = null;
            $tmp = null;
            $size = null;
        }

        $id = $newsFactory->addNews($userid, $res, $lat, $lng, $address, $name, $type, $tmp, $size);
        if ($id) {
             $newsId = $id;
             if ($newsId){
                $action = 'news';
                $action_id = $newsId;
                $votingTable = new Application_Model_Voting();
                $insert = $votingTable->firstNewsExistence($action, $action_id, $userid);
              }
         }
        
         //$vid  =  $votingTable->addNewsVote($userid);
        $newstable   = new Application_Model_News();
        $votingTable = new Application_Model_Voting();
        $newsRow = $newstable->getNews(array('id' => $id));
        $lastRowId = $newsFactory->getLastRow();

        $response->news = $newsRow->toArray();
        $response->noofvotes = $votingTable->getTotalVoteCounts('news', $newsRow['id'], $newsRow['user_id']);
       //echo "after number of votes"; exit;
        if (file_exists(realpath('.') . '/newsimages/' . $response->news['images'])) {
            list( $response->source_image_width, $response->source_image_height, $response->source_image_type ) =
				getimagesize(realpath('.') . '/newsimages/' . $response->news['images']);
        } else {
            $response->source_image_width = '';
            $response->source_image_height = '';
        }



        $response->id = $id;

        $response->image = Application_Model_User::getImage($userid);



        $response->lastRowId = '';

        if (isset($lastRowId))
            $response->lastRowId = $lastRowId;

        die(Zend_Json_Encoder::encode($response));
    }

    public function distance($lat1, $lon1, $lat2, $lon2, $unit) {

        $theta = $lon1 - $lon2;

        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));

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
	 *
	 *
	 * @return	void
	 */
	public function getNearbyPointsAction()
	{
		$response = array();

		try
		{
			$user_id = $this->_getParam('user_id');

			if (!Application_Model_User::checkId($user_id, $user))
			{
				throw new RuntimeException('Permission denied', -1);
			}

			$latitude = $this->_getParam('latitude');

			if (My_Validate::emptyString($latitude))
			{
				throw new RuntimeException('Latitude cannot be blank', -1);
			}

			if (!My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value: ' . var_export($latitude, true), -1);
			}

			$longitude = $this->_getParam('longitude');

			if (My_Validate::emptyString($longitude))
			{
				throw new RuntimeException('Longitude cannot be blank', -1);
			}

			if (!My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value: ' . var_export($longitude, true), -1);
			}

			$radius = $this->_getParam('radious', 1);

			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value: ' . var_export($radius, true), -1);
			}

			$fromPage = $this->_getParam('fromPage', 0);

			if (!My_Validate::digit($fromPage) || $fromPage < 0)
			{
				throw new RuntimeException('Incorrect fromPage value: ' . var_export($fromPage, true), -1);
			}

			$newsTable = new Application_Model_News;
			$select = $newsTable->select();

			$keywords = $this->_getParam('search_txt');

			if (!My_Validate::emptyString($keywords))
			{
				$select->where('news LIKE ?', '%' . $keywords . '%');
			}

			$filter = $this->_getParam('filter');

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
				case 'All':
				case null:
					$result = $newsTable->findByLocation($latitude, $longitude, $radius, 15, $fromPage, $select);
					break;
				default:
					throw new RuntimeException('Incorrect filter value: ' . var_export($filter, true), -1);
			}

			$commentTable = new Application_Model_Comments();

			$result = $result->toArray();
			$commentCount = array();
			$commentRow = array();

			foreach ($result as &$row)
			{
				if (!Application_Model_User::checkId($row['user_id'], $news_user))
				{
					throw new RuntimeException('Incorrect user ID: ' . var_export($row['user_id']), -1);
				}

				$commentCount[$row['id']] = $commentTable->getCountByNewsId($row['id']);
				$commentRow[$row['id']] = $commentTable->findAllByNewsId($row['id'], 2);

				$row['user'] = array(
					'name' => ucwords($news_user->Name),
					'image' => $news_user->getProfileImage(BASE_PATH . 'www/images/img-prof40x40.jpg'),
				);
			}

			$html = My_ViewHelper::render('home/new-news.html', array(
				'filter' => $filter,
				'auth' => $this->auth,
				'news' => $result,
				'comments' => $commentRow,
				'commentCount' => $commentCount,
				'fromPage' => $fromPage
			));

			$response['status'] = 1;
			$response['result'] = $result;
			$response['html'] = $html;
		}
		catch (Exception $e)
		{
			$response['status'] = 0;
			$response['error']['message'] = 'Internal server error';
		}

		die(Zend_Json_Encoder::encode($response));
	}

	// TODO: remove
    public function commentsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->layout->enableLayout();
    }

	// TODO: remove
    public function pagingAction() {
        $this->_helper->layout()->disableLayout();
    }

	// TODO: remove
    public function newNewsAction() {
        $this->_helper->layout()->disableLayout();
    }

    public function addNewCommentsAction()
	{
		$response = array();

		try
		{
			$identity = Zend_Auth::getInstance()->getIdentity();

			if (!$identity)
			{
				throw new RuntimeException('Session expired', -1);
			}

			if (!Application_Model_User::checkId($identity['user_id'], $user))
			{
				throw new RuntimeException('Incorrect user ID: ' . var_export($identity['user_id']), -1);
			}

			$news_id = $this->_getParam('news_id');

			if (!Application_Model_News::checkId($news_id, $news))
			{
				throw new RuntimeException('Incorrect news ID: ' . var_export($news_id), -1);
			}

			$comments = $this->_getParam('comments');

			if (trim($comments) === '' || strpos($comments, '<') > 0 || strpos($comments, '>') > 0)
			{
				throw new RuntimeException('Incorrect comment', -1);
			}

			$newsFactory = new Application_Model_NewsFactory();
			$comment_id = $newsFactory->addComments($comments, $news->id, $user->id);

			$votingTable = new Application_Model_Voting();
			$votingTable->measureLikeScore('news', $news->id, $user->id);

			$commentTable = new Application_Model_Comments();
			$comment_users = $commentTable->getAllCommentUsers($news->id, array($user->id, $news->user_id));

			$subject = 'SeeAroundme comment on your post';
			$body = My_Email::renderBody('comment-notify', array(
				'news' => $news,
				'user' => $user,
				'comment' => $comments
			));

			if (count($comment_users))
			{
				foreach ($comment_users as $row)
				{
					My_Email::send(array($row->Name => $row->Email_id), $subject, array('body' => $body));
				}
			}

			if ($news->user_id != $user->id)
			{
				Application_Model_User::checkId($news->user_id, $news_user);
				My_Email::send(array($news_user->Name => $news_user->Email_id), $subject, array('body' => $body));
			}

			$response['status'] = 1;
			$response['commentId'] = $comment_id;
		}
		catch (Exception $e)
		{
			$response['status'] = 0;
		}

		die(Zend_Json_Encoder::encode($response));
	}

    public function getTotalCommentsAction()
	{
		$response = array();

		try
		{
			$news_id = $this->_getParam('news_id');

			if (!Application_Model_News::checkId($news_id, $news))
			{
				throw new RuntimeException('Incorrect news ID');
			}

			$limitstart = $this->_getParam('limitstart');

			if (!My_Validate::digit($limitstart))
			{
				throw new RuntimeException('Incorrect limitstart value');
			}

			$comentsTable = new Application_Model_Comments;
			$comments = $comentsTable->findAllByNewsId($news_id, $comentsTable->news_limit, $limitstart);

			if (count($comments))
			{
				foreach ($comments as $comment)
				{
					$response['data'][] = My_ViewHelper::render('home/comment-item.html', array('comment' => $comment));
				}

				$count = max($comentsTable->getCountByNewsId($news_id) - ($limitstart + $comentsTable->news_limit), 0);

				if ($count)
				{
					$response['label'] = $comentsTable->viewMoreLabel($count, $comentsTable->news_limit);
				}
			}

			$response['status'] = 1;
		}
		catch (Exception $e)
		{
			$response['status'] = 0;
		}

        die(Zend_Json_Encoder::encode($response));
    }

    public function totalCommentsAction() {

        $this->_helper->layout()->disableLayout();
    }

public function changeAddressAction() {

        $response = new stdClass();

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        $addressArray = array(
            'address' => $this->_getParam('address'),
            'latitude' => $this->_getParam('latitude'),
            'longitude' => $this->_getParam('longitude')
        );

        $addressRow = null;

		$addressTable = new Application_Model_Address;
		
        if ($auth['user_id']) {

            $addressRow = $addressTable->searchRow('user_id', $auth['user_id']);

            $authObj = Zend_Auth::getInstance();

            $authData = array();

            $authData['user_name'] = $auth['user_name'];

            $authData['user_id'] = $auth['user_id'];

            $authData['pro_image'] = $auth['pro_image'];

            $authData['address'] = $addressRow->address;

            $authObj->getStorage()->write($authData);

            $this->view->latitude = $this->_getParam('latitude');

            $this->view->longitude = $this->_getParam('longitude');
        }

        $response->result = $returnRow = $addressTable->saveAddress($addressArray, $addressRow);

        if ($returnRow) {

            $auth1 = Zend_Auth::getInstance();

            $authData['latitude'] = $this->_getParam('latitude');

            $authData['longitude'] = $this->_getParam('longitude');

            $authData['address'] = $this->_getParam('address');

            $auth1->getStorage()->write($authData);
        }

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        die(Zend_Json_Encoder::encode($response));
 }
    
 
 /*************************************************************
      Currntly not in use but prevent to future perspective 
 **************************************************************/                                               
  
 public function changePostingLocationAction() {
        $response = new stdClass();
        $auth = Zend_Auth::getInstance()->getStorage()->read();
        $addressArray = array(
            'address' => $this->_getParam('address'),
            'latitude' => $this->_getParam('latitude'),
            'longitude' => $this->_getParam('longitude')
        );

        $addressRow = null;

        if ($auth['user_id']) {

            $addressRow = Application_Model_Address::searchRow('user_id', $auth['user_id']);

            $authObj = Zend_Auth::getInstance();

            $authData = array();

            $authData['user_name'] = $auth['user_name'];

            $authData['user_id'] = $auth['user_id'];

            $authData['pro_image'] = $auth['pro_image'];

            $authData['address'] = $addressRow->address;

            $authObj->getStorage()->write($authData);

            $this->view->latitude = $this->_getParam('latitude');

            $this->view->longitude = $this->_getParam('longitude');
        }

       //$response->result = $returnRow = Application_Model_Address::saveAddress($addressArray, $addressRow);

      $response->result =1;
       // if ($returnRow) {
         if (1) {
            $auth1 = Zend_Auth::getInstance();

            $authData['latitude'] = $this->_getParam('latitude');

            $authData['longitude'] = $this->_getParam('longitude');

            $authData['address'] = $this->_getParam('address');

            $auth1->getStorage()->write($authData);
        } 

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        //echo "<pre>"; print_r($auth);
        //echo "<pre>"; print_r($response); exit;
        die(Zend_Json_Encoder::encode($response));
    }   

    public function deleteAction() {

        $response = new stdClass();

        if ($this->_request->isPost()) {

            $data = $this->_request->getPost();

            if ($data['action'] == 'user') {

                $userTable = new Application_Model_User();

                if ($row = $userTable->find($data['id'])->current()) {

                    //$row->delete();

                    $response->success = "deleted successfully";
                } else {

                    $response->error = 'Sorry! we are unable to performe delete action';
                }
            } elseif ($data['action'] == 'news') {

                $newsTable = new Application_Model_News();

                if ($row = $newsTable->find($data['id'])->current()) {

                    $row->delete();

                    $response->success = "deleted successfully";
                } else {

                    $response->error = 'Sorry! we are unable to performe delete action';
                }
            } elseif ($data['action'] == 'comments') {

                $commentTable = new Application_Model_Comments();

                if ($row = $commentTable->find($data['id'])->current()) {

                    $row->delete();

                    $response->success = "deleted successfully";
                } else {

                    $response->error = 'Sorry! we are unable to performe delete action';
                }
            }
        } else {

            $response->error = 'Sorry! we are unable to performe delete action';
        }

        if ($this->_request->isXmlHttpRequest()) {

            die(Zend_Json_Encoder::encode($response));
        }
    }

    /*

      function to store voting value by user

      @created by : D

      @created date : 28/12/2012

     */

    public function storeVotingAction() {

        $response = new stdClass();

        if ($this->_request->isPost()) {

            $data = $this->_request->getPost();

            $userTable = new Application_Model_User();

            $votingTable = new Application_Model_Voting();

            $row = $votingTable->saveVotingData($data['action'], $data['id'], $data['user_id']);

            if ($row) {
                $response->successalready = 'registered already';
                $response->noofvotes_1 = $votingTable->getTotalVoteCounts($data['action'], $data['id'], $data['user_id']);
            } else {
                $response->success = 'voted successfully';
                $response->noofvotes_2 = $votingTable->getTotalVoteCounts($data['action'], $data['id'], $data['user_id']);
                 /*Code for score measurement*/
                $score = $votingTable->measureLikeScore($data['action'], $data['id'], $data['user_id']);
            }
          
            if ($this->_request->isXmlHttpRequest()) {

                die(Zend_Json_Encoder::encode($response));
            }
        } else {

            echo "Sorry unable to vote";
        }
    }
}
