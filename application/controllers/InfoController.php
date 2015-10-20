<?php

class InfoController extends Zend_Controller_Action
{
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
		}
		else
		{
			$user = (new Application_Model_User)->createRow();
		}

		$news_id = $this->_request->getParam('nwid');

		if (!Application_Model_News::checkId($news_id, $news, 0))
        {
			$this->_redirect($this->view->baseUrl('/'));
        }

		$owner = $news->findDependentRowset('Application_Model_User')->current();

		$this->view->item = $news;
		$this->view->user = $user;
		$this->view->owner = $owner;

		$this->view->headLink()
			->appendStylesheet(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css', $this->view));

		$this->view->headScript()
			->appendScript("	var news = " . json_encode(array(
				'id' => $news->id,
				'address' => $news->Address,
				'news' => $news->news,
				'latitude' => $news->latitude,
				'longitude' => $news->longitude,
			)) . ";\n" .
			"	var newsOwner = " . json_encode(array(
				'address' => $owner->address(),
				'name' => $owner->Name,
				'image' => $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
			)) . ";\n")
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile(My_Layout::assetUrl('bower_components/jquery.scrollTo/jquery.scrollTo.min.js', $this->view))
			->appendFile(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js', $this->view))
			->appendFile(My_Layout::assetUrl('bower_components/textarea-autosize/src/jquery.textarea_autosize.js', $this->view))
			->appendFile(My_Layout::assetUrl('www/scripts/news.js', $this->view));

		$this->view->doctype('XHTML1_RDFA');
		$this->view->headMeta()
			->setProperty('og:url', $this->view->serverUrl() . $this->view->baseUrl("info/news/nwid/" . $news->id))
			->setProperty('og:title', 'SeeAround.me')
			->setProperty('og:description', My_StringHelper::stringLimit($news->news, 155, '...'));

		$image = $news->findManyToManyRowset('Application_Model_Image',
			'Application_Model_NewsImage')->current();

		if ($image)
		{
			$thumb = $image->findThumb(array(320, 320));
		}
		else
		{
			$link = $news->findDependentRowset('Application_Model_NewsLink')->current();

			if ($link)
			{
				$image = $newsLink->findManyToManyRowset('Application_Model_Image',
					'Application_Model_NewsLinkImage')->current();

				if ($image)
				{
					$thumb = $image->findThumb(array(448, 320));
				}
			}

			if (!$image)
			{
				$image = $owner->findManyToManyRowset('Application_Model_Image',
					'Application_Model_UserImage')->current();

				if ($image)
				{
					$thumb = $image->findThumb(array(320, 320));
				}
			}
		}

		if ($image)
		{
			$imageUrl = $thumb->path;
			$imageWidth = $thumb->width;
			$imageHeight = $thumb->height;
		}
		else
		{
			// TODO: refactoring
			$imageUrl = 'www/images/img-prof200x200.jpg';
			$imageWidth = 200;
			$imageHeight = 200;
		}

		$this->view->headMeta($this->view->serverUrl() .
			$this->view->baseUrl($imageUrl), 'og:image', 'property')
			->setProperty('og:image:width', $imageWidth)
			->setProperty('og:image:height', $imageHeight);
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
