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
            $this->view->publicProfile = true;
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

		$mediaversion = Zend_Registry::get('config_global')->mediaversion;

		$this->view->headScript()
			->prependFile('/www/scripts/publicNews.js?' . $mediaversion)
			->prependFile('/www/scripts/news.js?' . $mediaversion);
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

	/**
	 * Function to share public post through mail.
	 *
	 * @return	void
	 */
	public function publicMessageEmailAction() 
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$news_id = $this->_request->getPost('news_id');

			if (!Application_Model_News::checkId($news_id, $news))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			$to = $this->_request->getPost('to');
			$message = $this->_request->getPost('message');

			My_Email::send($to, 'Interesting local news', array(
				'template' => 'post-share',
				'assign' => array(
					'user' => $user,
					'news' => $news,
					'message' => $message,
				)
			));

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array(
					'message' => 'Internal server error'
				)
			);
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





