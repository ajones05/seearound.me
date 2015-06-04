<?php

class HomeController extends Zend_Controller_Action
{
    public function init() {
          /* Initialize action controller here */
         $this->view->changeLocation = false;
    }

	/**
	 * Index action.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			$this->_redirect($this->view->baseUrl('/'));
		}

        $this->view->homePageExist = true;
        $this->view->changeLocation = true;
		$this->view->user = $user;

		$mediaversion = Zend_Registry::get('config_global')->mediaversion;

		$this->view->headLink()
			->appendStylesheet($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css'));

		$this->view->headScript()
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile($this->view->baseUrl('bower_components/jquery.scrollTo/jquery.scrollTo.min.js'))
			->appendFile($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js'))
			->appendFile($this->view->baseUrl('bower_components/textarea-autosize/src/jquery.textarea_autosize.js'))
			->appendFile($this->view->baseUrl('www/scripts/news.js?' . $mediaversion))
			->appendFile($this->view->baseUrl('www/scripts/homeindex.js?' . $mediaversion));
	}

	/**
	 * Logout action.
	 *
	 * @return void
	 */
	public function logoutAction()
	{
		$auth = Zend_Auth::getInstance();
		$data = $auth->getIdentity();

		if ($data)
		{
			$status = Application_Model_Loginstatus::getInstance()->find($data['login_id'])->current();

			if ($status)
			{
				$status->logout_time = date('Y-m-d H:i:s');
				$status->save();
			}

			$auth->clearIdentity();
		}

		$this->_redirect($this->view->baseUrl('/'));
	}

    public function editProfileAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$form = new Application_Form_Profile;

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();

			if ($form->isValid($data))
			{
				(new Application_Model_User)->updateProfile($user, $form->getValues());
				$this->_redirect($this->view->baseUrl("home/profile"));
			}
        }
		else
		{
			$form->populate(array(
				'email' => $user->Email_id,
				'public_profile' => $user->public_profile,
				'name' => $user->Name,
				'gender' => $user->Gender,
				'activities' => $user->Activities,
				'address' => $user->address,
				'latitude' => $user->latitude,
				'longitude' => $user->longitude,
			));

			if ($user->Birth_date != null)
			{
				$birth_time = strtotime($user->Birth_date);

				$form->populate(array(
					'birth_day' => date('d', $birth_time),
					'birth_month' => date('m', $birth_time),
					'birth_year' => date('Y', $birth_time),
				));
			}
		}

		if ($form->latitude->getValue() == '' || $form->longitude->getValue() == '')
		{
			$geolocation = My_Ip::geolocation();
			$form->latitude->setValue($geolocation[0]);
			$form->longitude->setValue($geolocation[1]);
		}

		$this->view->headScript()
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile('/bower_components/jquery-form/jquery.form.js');

		$this->view->form = $form;
        $this->view->user = $user;
        $this->view->myeditprofileExist = true;
        $this->view->changeLocation = true;
    }

    public function imageUploadAction(){
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!Application_Model_User::checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

        $response = new stdClass();
        $newsFactory = new Application_Model_NewsFactory();
        if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
            $url = urldecode($newsFactory->imageUpload($_FILES['ImageFile']['name'], $_FILES['ImageFile']['size'], $_FILES['ImageFile']['tmp_name'], $user->id));
            $response->url = $this->view->baseUrl($url);
        }

        die(Zend_Json_Encoder::encode($response));
    }

	public function profileAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if ($auth && !Application_Model_User::checkId($auth['user_id'], $user))
		{
			$auth->clearIdentity();
			throw new RuntimeException('Incorrect user session', -1);
		}

		$user_id = $this->_request->getParam('user');

		if ($user_id)
		{
			if (!Application_Model_User::checkId($user_id, $profile))
			{
				throw new RuntimeException('Incorrect user ID', -1);
			}
		}
		else
		{
			if (!$auth)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$profile = $user;
		}

		$newsModel = new Application_Model_News;

		$latest_post = $newsModel->fetchRow(
			$newsModel->publicSelect()
				->where('user_id =?', $profile->id)
				->order('id DESC')
		);

		$this->view->currentPage = 'Profile';
		$this->view->myprofileExist = true;
		$this->view->auth_id = $auth ? $user->id : null;
		$this->view->profile = $profile;

		if ($auth && $user->id != $profile->id)
		{
			$this->view->friendStatus = (new Application_Model_Friends)->getStatus($user->id, $profile->id);
		}

		if (!$auth || $profile->id != $user->id)
		{
			$this->view->headScript()->appendScript('	var user_profile = ' . json_encode(array(
				'id' => $profile->id,
				'name' => ucwords($profile->Name),
				'image' => $profile->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
				'address' => $profile->address(),
				'lat' => $profile->lat(),
				'lng' => $profile->lng(),
				'latestPost' => $latest_post ? $latest_post->news : 'N/A'
			)) . ';');
		}

		$this->view->headLink()
			->appendStylesheet($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css'));

		$this->view->headScript()
			->appendScript("	var	reciever_userid = " . json_encode($profile->id) . ";\n")
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile($this->view->baseUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js'));
	}

	/**
	 * Add news action.
	 *
	 * @return void
	 */
	public function addNewsAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$form = new Application_Form_News;

			if (!$form->isValid($this->_request->getParams()))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$news = (new Application_Model_News)->save(array_merge($form->getValues(), array('user_id' => $user->id)));

			if (!Application_Model_Voting::getInstance()->firstNewsExistence('news', $news->id, $user->id))
			{
				throw new RuntimeException('Save voting error', -1);
			}

			$response = array(
				'status' => 1,
				'news' => array(
					'id' => $news->id,
					'news' => $news->news,
					'latitude' => $news->latitude,
					'longitude' => $news->longitude,
					'user' => array(
						'id' => $user->id,
						'name' => $user->Name,
						'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
					),
					'html' => My_ViewHelper::render(
						'news/item.html',
						array(
							'item' => $news,
							'user' => $user,
							'auth' => array(
								'id' => $user->id,
								'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
							),
						)
					)
				),
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

	/**
	 *
	 *
	 * @return	void
	 */
	public function getNearbyPointsAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
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

			$radius = $this->_getParam('radius', 0.8);

			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value: ' . var_export($radius, true), -1);
			}

			$radius = $radius - 0.01;

			$fromPage = $this->_getParam('fromPage', 0);

			if (!My_Validate::digit($fromPage) || $fromPage < 0)
			{
				throw new RuntimeException('Incorrect fromPage value: ' . var_export($fromPage, true), -1);
			}

			$newsTable = new Application_Model_News;
			$select = $newsTable->select();

			$keywords = $this->_getParam('search');

			if (!My_Validate::emptyString($keywords))
			{
				$select->where('news LIKE ?', '%' . $keywords . '%');
			}

			$filter = $this->_getParam('filter');

			$response = array();

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

			if (count($result))
			{
				$commentTable = new Application_Model_Comments;
				$votingTable = new Application_Model_Voting;

				foreach ($result as $row)
				{
					$user = Application_Model_User::findById($row->user_id);

					$response['result'][] = array(
						'id' => $row->id,
						'news' => $row->news,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'user' => array(
							'id' => $user->id,
							'name' => $user->Name,
							'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						),
						'html' => My_ViewHelper::render(
							'news/item.html',
							array(
								'item' => $row,
								'user' => $user,
								'auth' => array(
									'id' => $user->id,
									'image' => $user->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
								),
								'votings_count' => $votingTable->findCountByNewsId($row->id),
								'comments_count' => $commentTable->getCountByNewsId($row->id),
								'comments' => $commentTable->findAllByNewsId($row->id, 3)
							)
						)
					);
				}
			}
			elseif ($fromPage == 0)
			{
				$response['result'] = My_ViewHelper::render('news/empty.html');
			}

			$response['status'] = 1;
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

    public function addNewCommentsAction()
	{
		try
		{
			$identity = Zend_Auth::getInstance()->getIdentity();

			if (!$identity || !Application_Model_User::checkId($identity['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$data = $this->_request->getParams();

			if (empty($data['news_id']) || !Application_Model_News::checkId($data['news_id'], $news, 0))
			{
				throw new RuntimeException('Incorrect news ID');
			}

			$form = new Application_Form_Comment;

			$data['user_id'] = $user->id;

			if (!$form->isValid($data))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$model = new Application_Model_Comments;
			$comment = $model->createRow($form->getValues());
			$comment->save();

			Application_Model_Voting::getInstance()->measureLikeScore('news', $news->id, $user->id);

			$comment_users = $model->getAllCommentUsers($news->id, array($user->id, $news->user_id));

			if (count($comment_users) || $news->user_id != $user->id)
			{
				$subject = 'SeeAroundme comment on a post you commented on';
				$body = My_Email::renderBody('comment-notify-comment', array(
					'news' => $news,
					'user' => $user,
					'comment' => $comment->comment
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
					$news_user = $news->findDependentRowset('Application_Model_User')->current();

					My_Email::send(
						array($news_user->Name => $news_user->Email_id),
						'SeeAroundme comment on your post',
						array(
							'template' => 'comment-notify-owner',
							'assign' => array(
								'news' => $news,
								'user' => $user,
								'comment' => $comment->comment
							)
						)
					);
				}
			}

			$response = array(
				'status' => 1,
				'html' => My_ViewHelper::render('comment/item.html', array('item' => $comment))
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

    public function getTotalCommentsAction()
	{
		try
		{
			$news_id = $this->_getParam('news_id');

			if (!Application_Model_News::checkId($news_id, $news, 0))
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

			$response = array();

			if (count($comments))
			{
				foreach ($comments as $comment)
				{
					$response['data'][] = My_ViewHelper::render('comment/item.html', array('item' => $comment));
				}

				$count = max($comentsTable->getCountByNewsId($news_id) - ($limitstart + $comentsTable->news_limit), 0);

				if ($count)
				{
					$response['label'] = $comentsTable->viewMoreLabel($count);
				}
			}

			$response['status'] = 1;
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

        die(Zend_Json_Encoder::encode($response));
    }

	/**
	 * Change user address news action.
	 *
	 * @return void
	 */
	public function changeAddressAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$address = $this->_request->getPost('address');

			$latitude = $this->_request->getPost('latitude');

			if (!is_numeric($latitude) || !My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value', -1);
			}

			$longitude = $this->_request->getPost('longitude');

			if (!is_numeric($longitude) || !My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value', -1);
			}

			$user_address = $user->findDependentRowset('Application_Model_Address')->current();

			if (!$user_address)
			{
				$user_address = (new Application_Model_Address)->createRow(array(
					'user_id' => $user->id
				));
			}

			$user_address->address = $address;
			$user_address->latitude = $latitude;
			$user_address->longitude = $longitude;
			$user_address->save();

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				array('error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error'))
			);
		}

        $this->_helper->json($response);
	}

    public function deleteAction()
	{
        try
		{
			$id = $this->_request->getPost('id');

			if (!Application_Model_News::checkId($id, $news, 0))
			{
				throw new Exception('Incorrect news ID.');
			}

			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || !Application_Model_User::checkId($auth['user_id'], $user) ||
				$news->user_id != $user->id)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$news->isdeleted = 1;
			$news->save();

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Sorry! we are unable to performe delete action')
			);
		}

		die(Zend_Json_Encoder::encode($response));
    }

    public function deleteCommentAction()
	{
        try
		{
			$id = $this->_request->getPost('id');

			if (!Application_Model_Comments::checkId($id, $comment, 0))
			{
				throw new Exception('Incorrect comment ID.');
			}

			$news = $comment->findDependentRowset('Application_Model_News')->current();

			if ($news->isdeleted)
			{
				throw new RuntimeException('News does not exist', -1);
			}

			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!$auth || ($auth['user_id'] != $comment->user_id && $auth['user_id'] != $news->user_id))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$comment->isdeleted = 1;
			$comment->save();

			$response = array('status' => 1);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Sorry! we are unable to performe delete action')
			);
		}

		die(Zend_Json_Encoder::encode($response));
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

	/**
	 * Read more news action.
	 *
	 * @return void
	 */
	public function readMoreNewsAction()
	{
		try
		{
			if (!Application_Model_News::checkId($this->_request->getPost('id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			$response = array(
				'status' => 1,
				'html' => $news->renderContent()
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

	/**
	 * Edit news action.
	 *
	 * @return void
	 */
	public function editNewsAction()
	{
		try
		{
			$model = new Application_Model_News;

			if (!$model->checkId($this->_request->getPost('id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			if (!Application_Model_User::checkId(My_ArrayHelper::getProp(Zend_Auth::getInstance()->getIdentity(), 'user_id'), $user) ||
				$user->id != $news->user_id)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$response = array(
				'status' => 1,
				'news' => array(
					'latitude' => $news->latitude,
					'longitude' => $news->longitude,
					'address' => $news->Address,
					'news' => $news->news
				)
			);
		}
		catch (RuntimeException $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e->getMessage())
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

	/**
	 * Save news action.
	 *
	 * @return void
	 */
	public function saveNewsAction()
	{
		try
		{
			$model = new Application_Model_News;

			if (!$model->checkId($this->_request->getPost('id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user) || $user->id != $news->user_id)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$body = $this->_request->getPost('news');

			if (trim($body) === '')
			{
				throw new RuntimeException('News cannot be blank', -1);
			}

			$news = $model->save(array('news' => $body), $news);

			$response = array(
				'status' => 1,
				'html' => $news->renderContent()
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

	/**
	 * Save news location action.
	 *
	 * @return void
	 */
	public function saveNewsLocationAction()
	{
		try
		{
			$model = new Application_Model_News;

			if (!$model->checkId($this->_request->getPost('id'), $news, 0))
			{
				throw new RuntimeException('Incorrect news ID', -1);
			}

			if (!Application_Model_User::checkId(My_ArrayHelper::getProp(Zend_Auth::getInstance()->getIdentity(), 'user_id'), $user) ||
				$user->id != $news->user_id)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$address = $this->_request->getPost('address');

			$latitude = $this->_request->getPost('latitude');

			if (My_Validate::emptyString($latitude) || !My_Validate::latitude($latitude))
			{
				throw new RuntimeException('Incorrect latitude value', -1);
			}

			$longitude = $this->_request->getPost('longitude');

			if (My_Validate::emptyString($longitude) || !My_Validate::longitude($longitude))
			{
				throw new RuntimeException('Incorrect longitude value', -1);
			}

			$news->Address = $address;
			$news->latitude = $latitude;
			$news->longitude = $longitude;
			$news->save();

			$response = array('status' => 1);
		}
		catch (RuntimeException $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e->getMessage())
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => 'Internal Server Error')
			);
		}

		die(Zend_Json_Encoder::encode($response));
	}

	/**
	 * Confirm user email action.
	 *
	 * @return void
	 */
	public function regConfirmAction()
	{
		$this->view->layout()->setLayout('login');

		try
		{
			$id = $this->_request->getParam('id');
			$code = $this->_request->getParam('q');
			$user = (new Application_Model_User)->findByCode($code);

			if (!$user || $user->id != $id || $user->Status != 'inactive')
			{
				throw new RuntimeException('Incorrect user confirm code', -1);
			}

			$user->Status = 'active';
			$user->Conf_code = '';
			$user->save();

			$this->view->success = 'Email confirm success';
		}
		catch (RuntimeException $e)
		{
			$this->view->eroors = "Inactive link";
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}
}
