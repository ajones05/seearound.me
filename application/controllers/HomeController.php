<?php

class HomeController extends My_Controller_Action_Herespy {

    public function init() {
          /* Initialize action controller here */
         $this->view->changeLocation = false;
    }

    public function indexAction() {
        $this->view->homePageExist = true;
        $this->view->current_menu = "";
        $mySessionVaribale = new Zend_Session_Namespace('mySessionVaribale');
        if (isset($mySessionVaribale->current_menu)) {
            $this->view->current_menu = $mySessionVaribale->current_menu;
            unset($mySessionVaribale->current_menu);
        }
        $this->view->changeLocation = true;
        $this->view->type = $this->_request->getParam("type", '');
        if ($this->auth['latitude'] == "" && $this->auth['longitude'] == "") {
            $this->_redirect(BASE_PATH . 'home/edit-profile');
        }
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
 
        if (strpos($res, "'") > 0 || strpos($res, "<") > 0 || strpos($res, ">") > 0) {
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
        $result = $this->findNearestPoint($lat, $lng, 1, '');
        $page = $this->_getParam('page', 1);
        $paginator = Zend_Paginator::factory($result);
        $paginator->setItemCountPerPage(15);
        $paginator->setCurrentPageNumber($page);
        $this->view->news = $paginator;
        $lastRowId = $newsFactory->getLastRow();
        $this->view->pageNumber = 2;
        $paging = $this->view->action('paging', 'home', array());
        $response->news = $newsRow->toArray();
        $response->noofvotes = $votingTable->getTotalVoteCounts('news', $newsRow['id'], $newsRow['user_id']);
       //echo "after number of votes"; exit;
        if (file_exists(realpath('.') . '/newsimages/' . $response->news['images'])) {
            list( $response->source_image_width, $response->source_image_height, $response->source_image_type ) = getimagesize(realpath('.') . '/newsimages/' . $response->news['images']);
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
    
    public function addmobinewsAction() {
        $response = new stdClass();
        $this->_helper->layout()->disableLayout();
        $newsFactory = new Application_Model_NewsFactory();
        $votingTable = new Application_Model_Voting();
        $newsTable   = new Application_Model_News();
        $userid = $this->_request->getParam('user_id');  
        $res = $this->_request->getParam('news'); //$this->_request->getParam('email');
        $lat = $this->_request->getParam('latitude');
        $lng = $this->_request->getParam('longitude');
        $address = $this->_request->getParam('address');
        $base = $this->_request->getParam('encodedImage');
         echo 'test';  
         echo $userid.'<br/>';
         echo $res.'<br/>';
         echo $lat.'<br/>';
         echo $lng.'<br/>';
         echo $address.'<br/>';
         exit;
             
            $userid = 8;
            $res = 'hello this is news by mobi';
            $lat = 28.450695641903604;
            $lng = 77.49346993779295;
            $address = "Greater Noida Yamuna Expressway";
            //$base = 'data:image/jpeg;base64,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';
        $id = $newsFactory->addmobileNews($userid, $res, $lat, $lng, $address, $base);
        if ($id) {
            $newsId = $id;
            if ($newsId) {
                $action = 'news';
                $action_id = $newsId;
                $votingTable = new Application_Model_Voting();
                $insert = $votingTable->firstNewsExistence($action, $action_id, $userid);
                if (isset($insert)) {
                    $resonse = "POSTED";
                    echo(json_encode($resonse));
                } else {
                    $response = "NOT POSTED";
                    echo(json_encode($resonse));
                }
            }
        }
    }


    public function getLatestNewsAction() {

        $response  = new stdClass();

        $resultvote = new stdClass();

        $newsFactory = new Application_Model_NewsFactory();

        $userId = $this->_getParam('user_id');

        $latitude = $this->_getParam('latitude');

        $longitude = $this->_getParam('longitude');

        $result = $this->findNearestPoint($latitude, $longitude, 0.8, '', $this->auth['user_id'], 0, 15);

        //$resultvote         = $this->findNewsVoting($this->auth['user_id'],0,1000);

        $response->result = $result;
        // $response->vote     = $resultvote;
        die(Zend_Json_Encoder::encode($response));
        //die(Zend_Json_Encoder::encode($resultvote));
    }
    

    /*

      function for getting number of votes hits recieved by a particulaer news or post.

      @coded by: D

      @created:01-01-2013

     */

    public function findNewsVoting($user_id, $startPage = 0, $endPage = 10000) {

        try {

            $db = Zend_Registry::getInstance()->get('db');

            //$db->beginTransaction();

            if ($user_id) {

                $query = "CALL votingcount(" . $user_id . "," . $startPage . "," . $endPage . ")";
            } else {

                $query = "CALL votingcount(" . $user_id . "," . $startPage . "," . $endPage . ")";
            }

            $stmt = $db->query($query, array(1));

            $returnArray = $stmt->fetchAll();

            $stmt->closeCursor();

            //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // $db->closeConnection();
            //} 

            return($returnArray);
        } catch (Exception $e) {

            print_r($e->getMessage());

            exit;
        }
    }

    /* findNewsVoting function ends */

    public function getNearestPointsAction() {

        $response = new stdClass();

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        $userId = $this->_getParam('user_id');

        $latitude = $this->_getParam('latitude');

        $longitude = $this->_getParam('longitude');

        $radious = $this->getRequest()->getPost('radious', '1');

        $searchTxt = $this->_getParam('search_txt');

        $this->view->userImage = Application_Model_User::getImage($userId);



        $newsFactory = new Application_Model_NewsFactory();

        if ($radious) {

            $result = $this->findNearestPoint($latitude, $longitude, $radious, $searchTxt);
        } else {

            $result = $this->findNearestPoint($latitude, $longitude, 1, $searchTxt);
        }

        $selectedRow = array();

        $commentRow = array();

        $commentCount = array();

        $i = 0;

        foreach ($result as $row) {

            $selectedRow[] = $row;

            $selectedRow[$i]['time'] = $row['created_date'];//$newsFactory->calculate_time($row['created_date']);

            $selectedRow[$i++]['comments'] = $this->view->action('comments', 'home', '', array('comments' => $newsFactory->getCommentByNewsId($row['id'])));
        }



        $page = $this->_getParam('page', 1);

        $paginator = Zend_Paginator::factory($selectedRow);

        $paginator->setItemCountPerPage(15);

        $paginator->setCurrentPageNumber($page);

        $this->view->news = $paginator;



        foreach ($paginator as $row) {

            $commentRow[$row['id']] = $newsFactory->getCommentByNewsId($row['id']);
        }



        $commentCount = array();

        foreach ($paginator as $row) {

            $commentCount[$row['id']] = $newsFactory->countComments($row['id']);
        }



        $this->view->commentCount = $commentCount;

        $this->view->comments = $commentRow;



        if (count($result) > 15) {

            $this->view->pageNumber = 2;
        } else {

            $this->view->pageNumber = 0;
        }



        $paging = $this->view->action('paging', 'home', array());

        $html = $this->view->action('new-news', 'home', array());

        $response->result = $selectedRow;

        $response->html = $html;

        $response->paging = $paging;

        die(Zend_Json_Encoder::encode($response));
    }

    public function isNearestPoint($searchInArray, $searchArray) {

        $currentPocket = 0;

        foreach ($searchInArray as $dataPocket) {

            foreach ($dataPocket as $rows) {



                if (($rows['latitude'] == $searchArray['latitude']) && ($rows['longitude'] == $searchArray['longitude'])) {

                    return $currentPocket;
                }

                if ($this->distance($rows['latitude'], $rows['longitude'], $searchArray['latitude'], $searchArray['longitude'], 'n') <= 0.0189393939) {

                    return $currentPocket;
                }
            }

            $currentPocket++;
        }

        return -1;
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

    public function searchNearestNewsAction() {

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        $response   = new stdClass();

        $latitude   = $this->_getParam('latitude');

        $longitude  = $this->_getParam('longitude');

        $searchText = $this->_getParam('searchText') ? $this->_getParam('searchText') : null;

        $radious    = trim($this->_getParam('radious')) ? trim($this->_getParam('radious')) : '0.8';

        $fromPage   = $this->_getParam('fromPage') ? $this->_getParam('fromPage') : 0;

        $toPage     = $this->_getParam('endPage') ? $this->_getParam('endPage') : 16;

        $this->view->filter = $filter = $this->_getParam('filter');

        $userId = $this->_getParam('user_id');

        $this->view->userImage = Application_Model_User::getImage($userId);

        $newsFactory = new Application_Model_NewsFactory();

        if ($filter == 'all') {

            $result = $this->findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);
        } else {

            if ($filter == 'interest') {
                $interest = Application_Model_User::getUserInterest($userId);
                /* $userTable = new Application_Model_User();
                  $interest = $userTable->getUserInterest($userId); */

                $interest = explode(",", $interest);

                $i = 0;

                $where = '(';

                foreach ($interest as $interestValue) {

                    if (($i > 0) && (trim($where) != '('))
                        $where .= ' or ';

                    if (trim($interestValue))

                    //$where .= ' t1.news like "%'.urlencode(trim($interestValue)).'%"  '; 
                        $where .= ' t1.news like "%' . trim($interestValue) . '%"  ';
                    $i++;
                }

                if (trim($where) != '(')
                    $where .= ')';

                else
                    $where = 'NULL';

                $response->interest = count($interest);



                $result = $this->findSearchingInterest($latitude, $longitude, $radious, $where, $userId, $fromPage, $toPage);
                $resultVote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
            } else if ($filter == 'myconnection') {

                $result = $this->findSearchingMyFriends($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);

                $resultVote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
            } else if ($filter == 'friends') {

                $result = $this->findSearchingMyFriends($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);

                $resultVote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
            } else {
                $result = $this->findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId, $fromPage, $toPage);
                $resultVote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
            }
        }



        $commentRow = array();

        $selectedRow = array();

        $commentCount = array();

        $selectedRowVote = array();

        $i = 0;



        foreach ($resultVote as $rowVote) {

            $countArray[$rowVote['news_id']] = $rowVote['counts'];
        }

        foreach ($result as $row) {

            $selectedRow[] = $row;

            $selectedRow[$i]['time'] = $row['created_date'];//$newsFactory->calculate_time($row['created_date']);

            $selectedRow[$i]['count'] = $countArray[$row['id']];

            $selectedRow[$i++]['comments'] = $this->view->action('comments', 'home', '', array('comments' => $newsFactory->getCommentByNewsId($row['id'])));
        }



        $page = $this->_getParam('page', 1);

        $paginator = Zend_Paginator::factory($selectedRow);

        $paginator->setItemCountPerPage(15);

        $paginator->setCurrentPageNumber($page);

        $this->view->news = $paginator;



        foreach ($paginator as $row) {

            $commentRow[$row['id']] = $newsFactory->getCommentByNewsId($row['id']);
        }

        $commentCount = array();

        foreach ($paginator as $row) {

            $commentCount[$row['id']] = $newsFactory->countComments($row['id']);
        }



        $this->view->commentCount = $commentCount;

        $this->view->comments = $commentRow;



        if (count($result) > 15) {

            $this->view->pageNumber = 2;
        } else {

            $this->view->pageNumber = 0;
        }



        $paging = $this->view->action('paging', 'home', array());

        $html = $this->view->action('new-news', 'home', array());

        $selectedRow1 = array();

        $i = 0;

        $totalPosts = 1;

        foreach ($result as $row) {

            $flag = true;

            if ($i) {

                $previousIndexNumber = $this->isNearestPoint($selectedRow1, $row);

                if ($previousIndexNumber != -1) {

                    array_push($selectedRow1[$previousIndexNumber], $row);

                    $flag = false;
                }
            }

            if ($flag) {

                $selectedRow1[$i++] = array($row);
            }

            $totalPosts++;

            if ($totalPosts > 15) {

                break;
            }
        }



        $response->markerData = $selectedRow1;

        $response->size = count($result);

        $response->html = $html;

        $response->paging = $paging;

        $response->resultvote = $selectedRowVote;



        die(Zend_Json_Encoder::encode($response));
    }

    public function getNearbyPointsAction() {
        $response  = new stdClass();
        $auth      = Zend_Auth::getInstance()->getStorage()->read();
        $userId    = $this->_getParam('user_id');
        $latitude  = $this->_getParam('latitude');
        $longitude = $this->_getParam('longitude');
        $radious   = $this->getRequest()->getPost('radious', '1');
        // $fromPage = start of page number, $toPage = end of page number
        $startPage = $this->_getParam('fromPage') ? $this->_getParam('fromPage') : 0;
        $endPage   = $this->_getParam('endPage')  ? $this->_getParam('endPage') : 16;
        // $fromPage = start of page number, $toPage = end of page number
        $searchTxt = $this->_getParam('search_txt');
        $this->view->userImage = Application_Model_User::getImage($userId);
        $newsFactory = new Application_Model_NewsFactory();
        
        if ($radious) {
             $result = $this->findNearestPoint($latitude, $longitude, $radious, $searchTxt, null, $startPage, $endPage);
             //$resultvote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
        } else {
             $result = $this->findNearestPoint($latitude, $longitude, 1, $searchTxt, null, $startPage, $endPage);
             //$resultvote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
            //$response->vote     = $resultvote;
       }
        
        //echo "<pre>"; echo "here"; print_r($result); exit;
        
        $selectedRow     = array();
        $commentRow      = array();
        $commentCount    = array();
        $selectedRowVote = array();
        $i = 0;
        $countArray = array();
        foreach ($resultvote as $rowvote) {
            $countArray[$rowvote['news_id']] = $rowvote['counts'];
        }

        /* foreach($resultvote as $voterow){

          $selectedRowVote[]=$voterow;

          $selectedRowVote[$i]['counts']      = $voterow['counts'];

          $selectedRowVote[$i++]['news_id']   = $voterow['news_id'];

          } */

        foreach ($result as $row) {
            $selectedRow[] = $row;
            $selectedRow[$i]['time'] = $row['created_date'];//$newsFactory->calculate_time($row['created_date']);
            $selectedRow[$i]['count'] = $countArray[$row['id']];
            $selectedRow[$i++]['comments'] = $this->view->action('comments', 'home', '', array('comments' => $newsFactory->getCommentByNewsId($row['id'])));
        }
        
        $page = $this->_getParam('page', 1);
        $paginator = Zend_Paginator::factory($selectedRow);
        $paginator->setItemCountPerPage(15);
        $paginator->setCurrentPageNumber($page);
        //echo "<pre>"; echo "before pagination"; print_r($paginator); exit;
        $this->view->news = $paginator;

        foreach ($paginator as $row) {
            $commentRow[$row['id']] = $newsFactory->getCommentByNewsId($row['id']);
        }

        $commentCount = array();
        foreach ($paginator as $row) {
            $commentCount[$row['id']] = $newsFactory->countComments($row['id']);
        }

        $this->view->commentCount = $commentCount;
        $this->view->comments = $commentRow;

        if (count($result) > 15) {
            $this->view->pageNumber = 2;
        } else {
            $this->view->pageNumber = 0;
        }

        $counter = 0;
        $paging = $this->view->action('paging', 'home', array());
        $html   = $this->view->action('new-news', 'home', array());
        $selectedRow1 = array();
        $i = 0;
        $totalPosts = 1;
        foreach ($result as $row) {
            $flag = true;
            if ($i) {
                $previousIndexNumber = $this->isNearestPoint($selectedRow1, $row);
                if ($previousIndexNumber != -1) {
                    array_push($selectedRow1[$previousIndexNumber], $row);
                    $flag = false;
                }
            }
            if ($flag) {
                $selectedRow1[$i++] = array($row);
            }
            $totalPosts++;
            if ($totalPosts > 15) {
                break;
            }
        }

        $response->size = count($result);
        $response->result = $selectedRow1;
        $response->resultvote = $selectedRowVote;
        $response->html = $html;
        $response->paging = $paging;
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function requestNearestAction() {
        echo "Here"; exit;
        echo "<pre>"; print_r($_REQUEST); exit;
        $response  = new stdClass();
        $auth      = Zend_Auth::getInstance()->getStorage()->read();
        $userId = trim($_REQUEST['user_id']);
        $latitude  = trim($_REQUEST['latitude']);
        $longitude = trim($_REQUEST['longitude']);
        if(isset($_REQUEST['radious'])){
          $radious   = trim($_REQUEST['radious']); 
        } else {
         $radious   = 0.8;       
        }
          // $fromPage = start of page number, $toPage = end of page number
        $startPage = $_REQUEST['fromPage'] ? $_REQUEST['fromPage'] : 0;
        $endPage   = $_REQUEST['endPage']  ? $_REQUEST['endPage']  : 16;
        $searchTxt = ''; 
       
      /*  $userId    = 3;
        $latitude  = 37.800554293249476;
        $longitude = -122.25412403173834;
        $radious   = 0.8;
        $startPage = $this->_getParam('fromPage') ? $this->_getParam('fromPage') : 0;
        $endPage   = $this->_getParam('endPage')  ? $this->_getParam('endPage') : 16;
        $searchTxt = ''; */
        
        $this->view->userImage = Application_Model_User::getImage($userId);
        $newsFactory = new Application_Model_NewsFactory();
        if ($radious) {
             $result = $this->findNearestPoint($latitude, $longitude, $radious, $searchTxt, null, $startPage, $endPage);
             //$resultvote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
        } else {
             $result = $this->findNearestPoint($latitude, $longitude, 1, $searchTxt, null, $startPage, $endPage);
             //$resultvote = $this->findNewsVoting($this->auth['user_id'], 0, 1000);
             //$response->vote     = $resultvote;
       }
        
        $selectedRow     = array();
        $commentRow      = array();
        $commentCount    = array();
        $selectedRowVote = array();
        $i = 0;
        $countArray = array();
        foreach ($resultvote as $rowvote) {
            $countArray[$rowvote['news_id']] = $rowvote['counts'];
        }
         
        foreach ($result as $row) {
            $selectedRow[] = $row;
            $selectedRow[$i]['time'] = $row['created_date'];//$newsFactory->calculate_time($row['created_date']);
            $selectedRow[$i]['count'] = $countArray[$row['id']];
            $selectedRow[$i++]['comments'] = $this->view->action('comments', 'home', '', array('comments' => $newsFactory->getCommentByNewsId($row['id'])));
        }

        $page = $this->_getParam('page', 1);
        $paginator = Zend_Paginator::factory($selectedRow);
        $paginator->setItemCountPerPage(15);
        $paginator->setCurrentPageNumber($page);
       
        $this->view->news = $paginator;

        foreach ($paginator as $row) {
            $commentRow[$row['id']] = $newsFactory->getCommentByNewsId($row['id']);
        }

        $commentCount = array();
        foreach ($paginator as $row) {
            $commentCount[$row['id']] = $newsFactory->countComments($row['id']);
        }

        $this->view->commentCount = $commentCount;
        $this->view->comments = $commentRow;

        if (count($result) > 15) {
            $this->view->pageNumber = 2;
        } else {
            $this->view->pageNumber = 0;
        }

        $counter = 0;
        $paging = $this->view->action('paging', 'home', array());
        $html   = $this->view->action('new-news', 'home', array());
        $selectedRow1 = array();
        $i = 0;
        $totalPosts = 1;
        foreach ($result as $row) {
            $flag = true;
            if ($i) {
                $previousIndexNumber = $this->isNearestPoint($selectedRow1, $row);
                if ($previousIndexNumber != -1) {
                    array_push($selectedRow1[$previousIndexNumber], $row);
                    $flag = false;
                }
            }
            if ($flag) {
                $selectedRow1[$i++] = array($row);
            }
            $totalPosts++;
            if ($totalPosts > 15) {
                break;
            }
        }

        $response->size = count($result);
        $response->result = $selectedRow1;
        $response->resultvote = $selectedRowVote;
       // $response->html = $html;
        //$response->paging = $paging;
        die(Zend_Json_Encoder::encode($response));
    }
    
    

    public function commentsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->layout->enableLayout();
    }

    public function pagingAction() {
        $this->_helper->layout()->disableLayout();
    }

    public function newNewsAction() {
        $this->_helper->layout()->disableLayout();
    }

    public function addNewCommentsAction() {
        
        $commentTable = new Application_Model_Comments();
        $newsTable = new Application_Model_News();
        $response = new stdClass();
        $comments = $this->_getParam('comments');
        $newsId = $this->_getParam('news_id');
        $userId = $this->_getParam('user_id');

        if (strpos($comments, "'") > 0 || strpos($comments, "<") > 0 || strpos($comments, ">") > 0) {
            $response->commentId = '';
            $response->image = '';
            die(Zend_Json_Encoder::encode($response));
        }
        
        $newsRow = $newsTable->getNewsWithDetails($newsId);
        $newsFactory = new Application_Model_NewsFactory();

        if ($comments != '') {
            $id = $newsFactory->addComments($comments, $newsId, $userId);
              $votingTable = new Application_Model_Voting();
             $score = $votingTable->measureLikeScore('news', $newsId, $userId);
        }

        $response->commentId = $id;
        $response->image = Application_Model_User::getImage($userId);

        /*

         * Code to send email to every user who made comments

         */

        if ($commentRows = $commentTable->getAllCommentUsers($newsId)) {
            //$newsRow = $newsTable->getNewsWithDetails($newsId);
            $url = BASE_PATH . "info/news/nwid/" . $newsId;
            $this->from = $this->auth['user_email'] . ':' . $this->auth['user_name'];
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
        die(Zend_Json_Encoder::encode($response));
    }

    public function getTotalCommentsAction() {

        $response = new stdClass();

        $newsId = $this->_getParam('news_id');



        $newsFactory = new Application_Model_NewsFactory();

        $comments = $newsFactory->viewTotalComments($newsId);

        $this->view->comments = $comments;

        $this->view->newsId = $newsId;

        $html = $this->view->action('total-comments', 'home', array());

        $response->comments = $html;

        die(Zend_Json_Encoder::encode($response));
    }

    public function totalCommentsAction() {

        $this->_helper->layout()->disableLayout();
    }

    public function searchNewsAction() {

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        $response = new stdClass();

        $latitude = $this->_getParam('latitude');

        $longitude = $this->_getParam('longitude');

        $searchText = $this->_getParam('searchText') ? $this->_getParam('searchText') : null;

        $radious = trim($this->_getParam('radious')) ? trim($this->_getParam('radious')) : '0.8';

        $this->view->filter = $filter = $this->_getParam('filter');

        $userId = $this->_getParam('user_id');

        $this->view->userImage = Application_Model_User::getImage($userId);

        $newsFactory = new Application_Model_NewsFactory();

        if ($filter == 'all') {

            $result = $this->findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId);
        } else {

            if ($filter == 'interest') {

                $interest = Application_Model_User::getIntrest($userId);

                if ($interest->Activities != '') {

                    $interest = Application_Model_User::getUserInterest($userId);

                    $interest = explode(",", $interest);

                    $i = 0;

                    $where = '(';

                    foreach ($interest as $interestValue) {

                        if (($i > 0) && (trim($where) != '('))
                            $where .= ' or ';

                        if (trim($interestValue))

                        //$where .= ' t1.news like "%'.urlencode(trim($interestValue)).'%"  '; 
                            $where .= ' t1.news like "%' . trim($interestValue) . '%"  ';
                        $i++;
                    }

                    if (trim($where) != '(')
                        $where .= ')';

                    else
                        $where = null;

                    $response->interest = count($interest);



                    $result = $this->findSearchingInterest($latitude, $longitude, $radious, $where, $userId, $fromPage, $toPage);
                } else {

                    $response->interest = 0;

                    $result = array();
                }
            } else if ($filter == 'myconnection') {

                $result = $this->findSearchingMyFriends($latitude, $longitude, $radious, $searchText, $userId);
            } else {

                $result = $this->findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId);
            }
        }



        $commentRow = array();

        $selectedRow = array();

        $i = 0;



        foreach ($result as $row) {

            $selectedRow[] = $row;

            $selectedRow[$i]['time'] = $row['created_date'];//$newsFactory->calculate_time($row['created_date']);

            $selectedRow[$i++]['comments'] = $this->view->action('comments', 'home', '', array('comments' => $newsFactory->getCommentByNewsId($row['id'])));
        }



        $page = $this->_getParam('page', 1);

        $paginator = Zend_Paginator::factory($selectedRow);

        $paginator->setItemCountPerPage(15);

        $paginator->setCurrentPageNumber($page);

        $this->view->news = $paginator;



        foreach ($paginator as $row) {

            $commentRow[$row['id']] = $newsFactory->getCommentByNewsId($row['id']);
        }

        $commentCount = array();

        foreach ($paginator as $row) {

            $commentCount[$row['id']] = $newsFactory->countComments($row['id']);
        }



        $this->view->commentCount = $commentCount;

        $this->view->comments = $commentRow;



        if (count($result) > 15) {

            $this->view->pageNumber = 2;
        } else {

            $this->view->pageNumber = 0;
        }



        $paging = $this->view->action('paging', 'home', array());

        $html = $this->view->action('new-news', 'home', array());

        $response->result = $selectedRow;

        $response->html = $html;

        $response->paging = $paging;


        die(Zend_Json_Encoder::encode($response));
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

        $response->result = $returnRow = Application_Model_Address::saveAddress($addressArray, $addressRow);

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
    

    public function newsPaggingAction() {

        $response = new stdClass();

        $newsFactory = new Application_Model_NewsFactory();

        if ($this->_request->isPost()) {

            $lat = $this->_request->getPost('lat', 0);

            $lng = $this->_request->getPost('lng', 0);

            $result = $this->findNearestPoint($lat, $lng, 1, '');

            $page = $this->_getParam('page', 1);

            $paginator = Zend_Paginator::factory($result);

            $paginator->setItemCountPerPage(15);

            $paginator->setCurrentPageNumber($page);

            $this->view->news = $paginator;

            if (count($result) > 15) {

                $lastRowId = $newsFactory->getLastRow();

                $this->view->pageNumber = 2;

                $paging = $this->view->action('paging', 'home', array());

                $response->paging = $paging;
            }
        }

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


 /*  storeVotingAction ends */
 public function findNearestPoint($latitude, $longitude, $radious, $text, $only = null, $startPage = 0, $endPage = 16) {

        try {

            $db = Zend_Registry::getInstance()->get('db');

            //$db->beginTransaction();

            if ($only) {
                $query = "CALL eventsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $text . "'," . $only . "," . $startPage . "," . $endPage . ")";
                //$query = "CALL eventsnear_1(" . $latitude . "," . $longitude . "," . $radious . ",'" . $text . "'," . $only . "," . $startPage . "," . $endPage . ")";
            } else {
                // $query = "CALL eventsnear_1(" . $latitude . "," . $longitude . "," . $radious . ",'" . $text . "',null," . $startPage . "," . $endPage . ")";
                $query = "CALL eventsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $text . "',null," . $startPage . "," . $endPage . ")";
          
            }
          
            $stmt = $db->query($query, array(1));

            $returnArray = $stmt->fetchAll();
            $stmt->closeCursor();

            //if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //$db->closeConnection();
            //} 
          
            return($returnArray);
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }

    public function findSearchingNearestPoint($latitude, $longitude, $radious, $searchText, $userId, $startPage = 0, $endPage = 16) {

        try {

            $db = Zend_Registry::getInstance()->get('db');

            $db->beginTransaction();
              /* procedure to call before changes */
              $query = "CALL searcheventsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "'," . $startPage . "," . $endPage . ")";
               //$query = "CALL searcheventsnear_1(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "'," . $startPage . "," . $endPage . ")";
                      
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

    public function findSearchingInterest($latitude, $longitude, $radious, $searchText, $userId, $startPage = 0, $endPage = 16) {

        try {

            $db = Zend_Registry::getInstance()->get('db');

            $db->beginTransaction();

             $query = "CALL searchinterestsnear_updated(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "'," . $startPage . "," . $endPage . ")"; //echo $query;
            //$query = "CALL searchinterestsnear_1(" . $latitude . "," . $longitude . "," . $radious . ",'" . $searchText . "','" . $userId . "'," . $startPage . "," . $endPage . ")"; //echo $query;
           

            
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

    /* searching friend's news */

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

    public function changeLinkAction() {

        $mySessionVaribale = new Zend_Session_Namespace('mySessionVaribale');

        $mySessionVaribale->current_menu = $this->_getParam('pageLink');

        $this->_helper->layout()->disableLayout();

        echo BASE_PATH . 'home';
    }

}

?>