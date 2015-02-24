<?php

class InfoController extends My_Controller_Action_Abstract
{

    public function init()
    {
        $this->view->request = $this->request = $this->getRequest();
        $auth = Zend_Auth::getInstance();
        $this->view->auth = $authData  = $auth->getIdentity();
        if(isset($authData['user_id'])) {
            $this->view->hideRight = true;
            $this->view->isLogin = true;
        } else {
            $this->view->layout()->setLayout('login');
            $this->view->publicProfile = ture;
        }
        /* Initialize action controller here */
    }
    public function newsAction(){
        $this->view->layout()->setLayout('layout');
        $this->view->hideRight = false;
        $this->view->publicMessage = true;
        $this->view->newsDetailExist = true;
        if(!$this->auth['user_id']) {
            $this->view->publicProfile = true;
        }
        /*
         * Getting news id from url
         */
        $this->view->newsId = $newsId = $this->_request->getParam("nwid", null);
        /*
         * setting return url
         */
        $this->view->returnUrl = BASE_PATH.'info/news/nwid/'.$newsId;
        /*
         * Creationg instance of model class 
         */
        $newTable    = new Application_Model_News();
        $newsFactory = new Application_Model_NewsFactory();
        $userTable   = new Application_Model_User();
        
        if($newsId) {
            /*
             * getting news record in the base of news id
             */
            $select = $newTable->select()->setIntegrityCheck(false)
                    ->from("news")
                    ->joinLeft("user_data", "user_data.id = news.user_id", array("uid"=>"id","Name", "image"=>"Profile_image"))
                    //->joinLeft("address", "address.user_id = user_data.id", array("address","latitude","longitude")) // previous code changes done on 07-10-2013
                    ->joinLeft("address", "address.user_id = user_data.id", array("address"))
                    ->where("news.id =? ", $newsId);                                                                                                
            if($this->view->newsData      = $newsRow = $newTable->fetchRow($select)) {
               $this->view->user_data     = $newsFactory->getUser(array("user_data.id" => $newsRow->uid)); 
               $this->view->newsTime      = $newsFactory->calculate_time($newsRow->created_date); 
               $this->view->commentData   = $newsFactory->viewTotalComments($newsRow->id);
               $this->view->totalcomments = Application_Model_Comments::getInstance()->getCountByNewsId($newsRow->id);
        /*
         * getting total number ogf vote for particular news id.
        */ 
              $votingsTable = new Application_Model_Voting();
              $selectQuery  = $votingsTable->countIndividualNewsVote($newsId);
              $this->view->newsVote = $selectQuery;
        /*
         * getting corresponding user with respect to news id
         * 
         */  
              if(isset($this->auth['user_id'])){
              $selectData   = $newTable->existUserId($newsId,$this->auth['user_id']); 
             // echo "<pre>"; print_r($selectData); exit;
              $this->view->userNewsId = $selectData;      
              }
            
            } else {
                /*
                * Handling invalid url call
                */
               $this->_redirect(BASE_PATH);
            }
            
        } else {
            /*
             * Handling invalid url call
             */
            $this->_redirect(BASE_PATH);
        }
    }
    
    public function getTotalCommentsAction()
    {
        $response = new stdClass();
        $newsId = $this->_getParam('news_id');

        $newsFactory = new Application_Model_NewsFactory();
        $comments = $newsFactory->viewTotalComments($newsId);
        $this->view->comments = $comments;
        $this->view->newsId = $newsId;
        $html = $this->view->action('total-comments', 'info', array()); 
        $response->comments = $html;
        die(Zend_Json_Encoder::encode($response));
    }
    
    public function totalCommentsAction()
    {
        $this->_helper->layout()->disableLayout();
    }
    
    /*
     * function to send public meaasage
     */
    public function publicMessageAction() 
    {
        /*
         * creating the instance of auth class
         */
        $response = new stdClass();
        if($this->_request->isPost()) {
            /*
             * accessing the requested parameters and setuping mail parameters
             */
            $message = $this->_request->getPost('message', null);
            $to = $this->_request->getPost('to', null);
            if(!$message) {
                $response->errors->message = "Invalid messags information";
            } elseif(!$to) {
                $response->errors->to = "Invalid reciever information";
            } else { 
                $this->to = $to;
                $this->from = $this->auth['user_email'];
                $this->subject = "News invitation";
                $this->view->name = $this->auth['user_name'];
                $this->view->message = "<p align='justify'>$message</p>";
                $this->view->adminName = "Admin";
                $this->view->response = "seearound.me";
                $this->message = $this->view->action("index","general",array());
                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                $response->success = "done";
            }
        } 
        die(Zend_Json_Encoder::encode($response));
    }


    /*
     * function to share public post through mail 
     */
    public function publicMessageEmailAction() 
    {
        /*
         * creating the instance of auth class
         */
        $response = new stdClass();
        if($this->_request->isPost()) {
            /*
             * accessing the requested parameters and setuping mail parameters
             */
            $message = $this->_request->getPost('message', null);
            $to = $this->_request->getPost('to', null);
            if(!$message) {
                $response->errors->message = "Invalid messags information";
            } elseif(!$to) {
                $response->errors->to = "Invalid reciever information";
            } else { 
                $this->name =$this->auth['user_name'];
                $this->to = $to;
                $this->from = $this->auth['user_email'];
                $this->subject = "News invitation";
                $this->view->name = "User";
                $this->view->message = "<p align='justify'> $this->name shared a local update with you -- you can see the post details and location here $message</p>";
                $this->view->adminName = "Admin";
                $this->view->response = "seearound.me";
                $this->message = $this->view->action("index","general",array());
                $this->sendEmail($this->to, $this->from, $this->subject, $this->message);
                $response->success = "done";
            }
        } 
        die(Zend_Json_Encoder::encode($response));
    }

    public function privacyAction()
    {
        // action body
    }

    public function aboutUsAction()
    {
        // action body
    }
     public function storeVotingIndividualAction(){
         $response = new stdClass();
         if($this->_request->isPost()) {
            $data = $this->_request->getPost();
           // $votingTable = new Application_Model_Voting();
           
            $userTable     = new Application_Model_User();
            $votingTable   = new Application_Model_Voting();
            $row =$votingTable->saveVotingData($data['action'],$data['id'],$data['user_id']);
            if($row){
               $response->successalready = 'registered already'; 
               $response->noofvotes_1 = $votingTable->getTotoalVoteCounts($data['action'],$data['id'],$data['user_id']);
               
              }
            else {
                $response->success =   'voted successfully';
                $response->noofvotes_2 = $votingTable->getTotoalVoteCounts($data['action'],$data['id'],$data['user_id']);
                //$response->successalready = 'registered already'; 
            } 
         /*$exist = checkExistUser($data['id'],$data['user_id']);
               if($exist){
                   $response->exist  ='User Exist';
               }  */
        if($this->_request->isXmlHttpRequest()) {
            die(Zend_Json_Encoder::encode($response));
         }
       } else {
           echo "Sorry unable to vote";
       }
     }
     
   
}    





