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

	/**
	 * News details action.
	 *
	 * @return void
	 */
    public function newsAction()
	{
        $this->view->layout()->setLayout('layout');
        $this->view->hideRight = false;
        $this->view->publicMessage = true;
        $this->view->newsDetailExist = true;

		$news_id = $this->_request->getParam('nwid');

		if (!Application_Model_News::checkId($news_id, $news) ||
			!Application_Model_User::checkId($news->user_id, $news_user))
        {
			$this->_redirect(BASE_PATH);
        }

		$this->view->news = $news;
		$this->view->news_user = $news_user;
		$this->view->returnUrl = BASE_PATH . 'info/news/nwid/' . $news->id;

		$this->view->comentsModel = new Application_Model_Comments;

		$this->view->comments = $this->view->comentsModel->findAllByNewsId($news->id, 5);
		$this->view->totalcomments = $this->view->comentsModel->getCountByNewsId($news->id);
		$this->view->newsVote = Application_Model_Voting::getInstance()->findCountByNewsId($news->id);

		$this->view->headScript()->prependFile('/www/scripts/publicNews.js?' . Zend_Registry::get('config_global')->mediaversion);
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





