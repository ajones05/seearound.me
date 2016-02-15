<?php

class InfoController extends Zend_Controller_Action
{
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
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}
}    
