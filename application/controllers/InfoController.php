<?php

class InfoController extends Zend_Controller_Action
{
	/**
	 * @var	Application_Model_UserRow
	 */
	protected $user;

    public function init()
    {
        $auth = Zend_Auth::getInstance();
        $authData  = $auth->getIdentity();
        if(isset($authData['user_id'])) {
            $this->view->hideRight = true;
        } else {
            $this->view->layout()->setLayout('login');
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
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!empty($auth['user_id']))
		{
			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('Incorrect user ID', -1);
			}

			$this->view->user = $user;
		}

        $this->view->layout()->setLayout('layout');
        $this->view->hideRight = false;
        $this->view->newsDetailExist = true;

		$news_id = $this->_request->getParam('nwid');

		if (!Application_Model_News::checkId($news_id, $news, 0))
        {
			$this->_redirect($this->view->baseUrl('/'));
        }

		$this->view->news = $news;
		$this->view->news_owner = $news->findDependentRowset('Application_Model_User')->current();
		$this->view->returnUrl = $this->view->baseUrl('info/news/nwid/' . $news->id);

		$this->view->comentsModel = new Application_Model_Comments;

		$this->view->comments = $this->view->comentsModel->findAllByNewsId($news->id, 5);
		$this->view->totalcomments = $this->view->comentsModel->getCountByNewsId($news->id);
		$this->view->newsVote = Application_Model_Voting::getInstance()->findCountByNewsId($news->id);

		$mediaversion = Zend_Registry::get('config_global')->mediaversion;

		$this->view->headLink()
			->appendStylesheet($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css'));

		$this->view->headScript()
			->appendScript("	var news = " . json_encode(array(
				'id' => $news->id,
				'address' => $news->Address,
				'news' => $news->news,
				'latitude' => $news->latitude,
				'longitude' => $news->longitude,
			)) . ";\n" .
			"	var newsOwner = " . json_encode(array(
				'address' => $this->view->news_owner->address(),
				'name' => $this->view->news_owner->Name,
				'image' => $this->view->news_owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
			)) . ";\n")
			->appendFile($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js'))
			->appendFile($this->view->baseUrl('bower_components/textarea-autosize/src/jquery.textarea_autosize.js'))
			->appendFile($this->view->baseUrl('www/scripts/news.js?' . $mediaversion));
    }

    public function totalCommentsAction()
    {
        $this->_helper->layout()->disableLayout();
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

			if (!Application_Model_News::checkId($news_id, $news, 0))
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
}    
