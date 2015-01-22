<?php



class GeneralController extends  My_Controller_Action_Abstract

{



    public function init()

    {

        /* Initialize action controller here */

        $this->view->request = $this->getRequest();

    }



    public function indexAction()

    {
      


    }

    public function confirmationAction()
    {
    }

    public function logoutAction()

    {

        $auth = Zend_Auth::getInstance();

        $authData = $auth->getIdentity();

        $loginStatus = new Application_Model_Loginstatus();

        $row = $loginStatus->find($authData['login_id'])->current();

        if($row) {

            $row->logout_time = date('Y-m-d H:i:s');

            $row->save();

        }

        $auth->clearIdentity();

        if($this->_request->getCookie('emailLogin') && $this->_request->getCookie('passwordLogin')) {

            setcookie("emailLogin", "", 0, '/');

            setcookie("passwordLogin", "", 0, '/');

            setcookie("keepLogin", "yes", time() +7*24*60*60, '/');

            $this->_redirect(BASE_PATH);

        } else {

            $this->_redirect(BASE_PATH);

        }

    }

    

    public function searchNewsAction()

    { 

        $auth = Zend_Auth::getInstance()->getStorage()->read();

        $response = new stdClass();

        $latitude = $this->_request->getPost('latitude');

        $longitude = $this->_request->getPost('longitude');

        $searchText = $this->_request->getPost('searchText');

        $radious = trim($this->_request->getPost('radious'))?trim($this->_request->getPost('radious')):50000;

        $this->view->filter = $filter = $this->_request->getPost('filter');

        $userId = $this->_request->getPost('user_id');  

        $this->view->userImage = Application_Model_User::getImage($userId);

        $newsFactory = new Application_Model_NewsFactory();

        if($filter=='all')

            $result = $this->findSearchingNearestPoint($latitude,$longitude,$radious,$searchText,$userId);

        else{

            if($filter=='interest'){ 

                $interest = Application_Model_User::getIntrest($userId);

                $i = 0;

                $where = '(';

                foreach($interest as $interestValue){

                    if($i>0)

                        $where .= ' or ';

                    $where .= ' t1.news like "%'.urlencode($interestValue).'%"  '; 

                    $i++;

                }

                $where .= ')'; 

                $response->interest = count($interest);

                $result = $this->findSearchingInterest($latitude,$longitude,$radious,$where,$userId); 

            } else {

                $result = $this->findSearchingNearestPoint($latitude,$longitude,1,$searchText,$userId);

            }    

        }

       

        $commentRow = array();

        $selectedRow = array();

        $i = 0;

        

        foreach($result as $row) { 

            $selectedRow[] =  $row;

            $selectedRow[$i]['time'] = $newsFactory->calculate_time($row['created_date']);

            $selectedRow[$i++]['comments'] = $this->view->action('comments', 'general', '', array('comments'=>$newsFactory->getCommentByNewsId($row['id'])));

        }

				

        $page=$this->_getParam('page', 1);

        $paginator = Zend_Paginator::factory($selectedRow);

        $paginator->setItemCountPerPage(10);

        $paginator->setCurrentPageNumber($page);

        $this->view->news = $paginator;

		

        foreach($paginator as $row) {

            $commentRow[$row['id']] = $newsFactory->getCommentByNewsId($row['id']);

        }		

        $commentCount = array();

        foreach($paginator as $row) {

            $commentCount[$row['id']] = $newsFactory->countComments($row['id']);

        }



        $this->view->commentCount = $commentCount;

        $this->view->comments = $commentRow;



        if(count($result) > 10) {

            $this->view->pageNumber = 2;

        } else {

            $this->view->pageNumber = 0;

        }



        $paging = $this->view->action('paging', 'general', array());

        $html = $this->view->action('new-news', 'general', array()); 

        $response->result = $selectedRow;

        $response->html = $html;

        $response->paging = $paging;



        die(Zend_Json_Encoder::encode($response));

    }

    

    public function commentsAction() 

    {

       $this->_helper->layout->disableLayout();

       

       $this->_helper->layout->enableLayout();

    }

    

    public function pagingAction()

    {

        $this->_helper->layout()->disableLayout();	

    }

	

    public function newNewsAction()

    {

        $this->_helper->layout()->disableLayout();

    }

    

    public function findSearchingNearestPoint($latitude,$longitude,$radious,$searchText,$userId) {

        try {

            $db = Zend_Registry::getInstance()->get('db');

            $db->beginTransaction();

            $query = "CALL searcheventsnear(".$latitude.",".$longitude.",".$radious.",'".$searchText."','".$userId."')";

            $stmt = $db->query($query,array(1));

            $returnArray = $stmt->fetchAll();

            $stmt->closeCursor();

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

                $db->closeConnection();

            } 

            return($returnArray);

        } 

        catch(Exception $e) {

            print_r($e->getMessage());

        exit;

        }

    }

    

    public function findSearchingInterest($latitude,$longitude,$radious,$searchText,$userId)

    {

        try {

            $db = Zend_Registry::getInstance()->get('db');

            $db->beginTransaction();

            $query = "CALL searchinterestsnear(".$latitude.",".$longitude.",".$radious.",'".$searchText."','".$userId."')";

            $stmt = $db->query($query,array(1));

            $returnArray = $stmt->fetchAll();

            $stmt->closeCursor();

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

             $db->closeConnection();

            } 

            return($returnArray);

        } 

        catch(Exception $e) {

            print_r($e->getMessage());

            exit;

        }

    }  

    

    public function inviteSelfAction()

    {   

        $emailInvites  = new Application_Model_Emailinvites();

        $emailToInvite = $emailInvites->returnEmailInvites();

        if($emailToInvite){

            foreach ($emailToInvite as $row) {

                $url = $url = BASE_PATH."index/send-invitation/regType/email/q/".$row->code;

                $this->to = $row->self_email;

                $this->subject = "Here Spy Invitation";

                $this->from = 'admin@herespy.com:Admin';

                $this->view->name = $row->self_email;

                

                $message = "To join Here-Spy, please click the link below!<br />".$url;



                $this->view->message = "<p align='justify'>$message</p>";

                $this->view->adminName = "Admin";

                $this->view->response = "Here Spy";

                $this->message = $this->view->action("index","general",array());

                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);

                $row->status = '1';

                $row->save();

                

            }

        }

        die();

    }

    

}



