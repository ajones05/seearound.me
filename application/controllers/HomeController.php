<?php
use Respect\Validation\Validator as v;

class HomeController extends Zend_Controller_Action
{
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

		$point = $this->_request->get('point');
		
		if (trim($point) !== '')
		{
			$point = explode(',', $point);

			if (count($point) != 2 || !My_Validate::latitude($point[0]) || !My_Validate::longitude($point[1]))
			{
				throw new RuntimeException('incorrect point value', -1);
			}

			$this->view->headScript('script', 'var point = ' . json_encode($point) . ';');
		}

		$center = $this->_request->get('center');
		
		if (trim($center) !== '')
		{
			$center = explode(',', $center);

			if (count($center) != 2 || !My_Validate::latitude($center[0]) || !My_Validate::longitude($center[1]))
			{
				throw new RuntimeException('Incorrect center value', -1);
			}

			$this->view->headScript('script', 'var mapCenter = ' . json_encode($center) . ';');
		}

		$radius = $this->_request->get('radius');

		if (trim($radius) !== '')
		{
			if (!is_numeric($radius) || $radius < 0.5 || $radius > 1.5)
			{
				throw new RuntimeException('Incorrect radius value', -1);
			}

			$this->view->headScript('script', 'var mapRadius = ' . $radius . ';');
		}

        $this->view->homePageExist = true;
        $this->view->changeLocation = true;
		$this->view->displayMapFilter = true;
		$this->view->displayMapSlider = true;
		$this->view->displayMapZoom = true;
		$this->view->user = $user;

		$this->view->headLink()
			->appendStylesheet(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css', $this->view));

		$addressModel = new Application_Model_Address;
		$userAddress = $user->findDependentRowset('Application_Model_Address')->current();

		$this->view->headScript()
			->appendScript('var profileData=' . json_encode([
				'address' => $addressModel->format($userAddress->toArray()),
				'latitude' => $userAddress->latitude,
				'longitude' => $userAddress->longitude
			]) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile(My_Layout::assetUrl('bower_components/jquery.scrollTo/jquery.scrollTo.min.js', $this->view))
			->appendFile(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js', $this->view))
			->appendFile(My_Layout::assetUrl('bower_components/textarea-autosize/src/jquery.textarea_autosize.js', $this->view))
			->appendFile(My_Layout::assetUrl('www/scripts/news.js', $this->view))
			->appendFile(My_Layout::assetUrl('www/scripts/homeindex.js', $this->view));
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
			$status = (new Application_Model_Loginstatus)->find($data['login_id'])->current();

			if ($status)
			{
				$status->logout_time = date('Y-m-d H:i:s');
				$status->save();
			}

			$auth->clearIdentity();
			Zend_Session::forgetMe();
		}

		$this->_redirect($this->view->baseUrl('/'));
	}

    public function editProfileAction()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();
		$userModel = new Application_Model_User;

		if (!$userModel->checkId($auth['user_id'], $user))
		{
			throw new RuntimeException('You are not authorized to access this action', -1);
		}

		$profileForm = new Application_Form_Profile;
		$addressModel = new Application_Model_Address;
		$addressForm = new Application_Form_Address;
		$userAddress = $user->findDependentRowset('Application_Model_Address')->current();

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();
			$validAddress = $addressForm->isValid($data);
			$validProfile = $profileForm->isValid($data);

			if ($validAddress)
			{
				if ($validProfile)
				{
					$userModel->updateProfile($user, $data);
					$this->_redirect($this->view->baseUrl("home/profile"));
				}

				$this->view->headScript('script', 'var formData=' . json_encode([
					'address' => $addressModel->format($data),
					'latitude' => $addressForm->latitude->getValue(),
					'longitude' => $addressForm->longitude->getValue()
				]) . ';');
			}
        }
		else
		{
			$addressForm->setDefaults($userAddress->toArray());
			$profileForm->setDefaults([
				'email' => $user->Email_id,
				'public_profile' => $user->getPublicProfile(),
				'name' => $user->Name,
				'gender' => $user->gender(),
				'activities' => $user->activities(),
				'latitude' => $userAddress->latitude,
				'longitude' => $userAddress->longitude,
			]);

			if ($user->Birth_date != null)
			{
				$birthbay = new DateTime($user->Birth_date);
				$profileForm->setDefaults([
					'birth_day' => $birthbay->format('d'),
					'birth_month' => $birthbay->format('m'),
					'birth_year' => $birthbay->format('Y')
				]);
			}
		}

		$addressFormat = $addressModel->format($userAddress->toArray());
		$this->view->addressFormat = $addressFormat;

		$this->view->headScript()
			->appendScript('var profileData=' . json_encode([
				'address' => $addressFormat,
				'latitude' => $userAddress->latitude,
				'longitude' => $userAddress->longitude
			]) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places')
			->appendFile(My_Layout::assetUrl('/bower_components/jquery-form/jquery.form.js', $this->view));

		$this->view->profileForm = $profileForm;
		$this->view->addressForm = $addressForm;
        $this->view->user = $user;
        $this->view->changeLocation = true;
    }

	public function imageUploadAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$upload = new Zend_File_Transfer;
			$upload->setValidators(array(
				array('Extension', false, array('jpg', 'jpeg', 'png', 'gif')),
				array('MimeType', false, array('image/jpeg', 'image/png', 'image/gif')),
				array('Count', false, 1)
			));

			if (!$upload->isValid('ImageFile'))
			{
				throw new RuntimeException(implode('. ', $upload->getMessages()), -1);
			}

			$ext = My_CommonUtils::$mimetype_extension[$upload->getMimeType('ImageFile')];

			do
			{
				$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
				$full_path = ROOT_PATH_WEB . '/www/upload/' . $name;
			}
			while (file_exists($full_path));

			$upload->addFilter('Rename', $full_path);
			$upload->receive();

			$currentImage = $user->findManyToManyRowset('Application_Model_Image',
				'Application_Model_UserImage')->current();

			if ($currentImage)
			{
				$currentImage->deleteImage();
			}

			$image = (new Application_Model_Image)->save('www/upload/' . $name);

			(new Application_Model_UserImage)->insert(array(
				'user_id' => $user->id,
				'image_id' => $image->id
			));

			$thumb320x320 = 'uploads/' . $name;

			My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path, array(
				array(320, 320, ROOT_PATH_WEB . '/' . $thumb320x320)
			));

			$thumb = (new Application_Model_ImageThumb)
				->save($thumb320x320, $image, array(320, 320));

			$response = array(
				'status' => 1,
				'url' => $this->view->baseUrl($thumb->path)
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

        $this->_helper->json($response);
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

		if ($auth)
		{
			$this->view->user = $user;
		}

		$this->view->auth_id = $auth ? $user->id : null;
		$this->view->profile = $profile;

		// TODO: check performance

		$this->view->karma_posts = $newsModel->fetchRow(
			$newsModel->publicSelect()
				->setIntegrityCheck(false)
				->from($newsModel, array(
					'IFNULL(SUM(if(news.user_id = "' . $profile->id . '", 1, 0)), 0) AS news_count',
					'IFNULL(SUM(comments.count), 0) as comments_count',
					'IFNULL(SUM(comments_other.count), 0) as other_comments_count',
					'IFNULL(SUM(if(news.user_id = "' . $profile->id . '", news.vote, 0)), 0) as votings_count',
				))
				->joinLeft(array('comments' => $newsModel->commentsSubQuery()), 'comments.news_id = news.id AND news.user_id = ' . $profile->id, '')
				->joinLeft(array('comments_other' => $newsModel->commentsSubQuery()), 'comments_other.news_id = news.id AND comments_other.user_id = ' .
					$profile->id . ' AND news.user_id <> ' . $profile->id, '')
		);

		$this->view->karma_comments = (new Application_Model_Comments)->getCountByUserId($profile->id);

		if ($auth && $user->id != $profile->id)
		{
			$isFriend = (new Application_Model_Friends)->isFriend($user, $profile);
			$this->view->headScript()
				->appendScript('var isFriend=' . ($isFriend ? 'true' : 'false') . ';');
			$this->view->isFriend = $isFriend;
		}

		$this->view->headLink()
			->appendStylesheet(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css', $this->view));

		$addressModel = new Application_Model_Address;
		$profileAddress = $profile->findDependentRowset('Application_Model_Address')->current();
		$addressFormat = $addressModel->format($profileAddress->toArray(), ['street' => false]);
		$this->view->addressFormat = $addressFormat;

		$config = Zend_Registry::get('config_global');
		$this->view->headScript()
			->appendScript('var reciever_userid=' . json_encode($profile->id) . ',' .
				'profileData=' . json_encode([
				'id' => $profile->id,
				'address' => $addressFormat,
				'latitude' => $profileAddress->latitude,
				'longitude' => $profileAddress->longitude
			]) . ';')
			->appendFile(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js', $this->view));

		My_Layout::appendAsyncScript('//maps.googleapis.com/maps/api/js?' .
				'key=' . $config->google->maps->key . '&sensor=false&v=3&callback=initMap', $this->view);
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

			$response = array(
				'status' => 1,
				'result' => array(
					array(
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
							array('user' => $user, 'item' => $news, 'owner' => $user, 'is_new' => true)
						)
					)
				)
			);

			if ($this->_request->getPost('reset_map', 0))
			{
				$result = (new Application_Model_News)->search(array(
					'latitude' => $news->latitude,
					'longitude' => $news->longitude,
					'radius' => 0.8,
					'limit' => 14,
					'exclude_id' => array($news->id)
				), $user);

				foreach ($result as $row)
				{
					$owner = $row->findDependentRowset('Application_Model_User')->current();
					$response['result'][] = array(
						'id' => $row->id,
						'news' => $row->news,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'user' => array(
							'id' => $owner->id,
							'name' => $owner->Name,
							'image' => $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						),
						'html' => My_ViewHelper::render(
							'news/item.html',
							array('user' => $user, 'item' => $row, 'owner' => $owner)
						)
					);
				}
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Load news action.
	 *
	 * @return void
	 */
	public function loadNewsAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$new = $this->_request->getPost('new', []);

			if (!v::optional(v::arrayVal())->validate($new))
			{
				throw new RuntimeException('Incorrect new value');
			}

			$point = $this->_request->getPost('point', 0);

			if (!v::optional(v::intVal())->validate($point))
			{
				throw new RuntimeException('Incorrect point value');
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radius', 0.8),
				'keywords' => $this->_request->getPost('keywords'),
				'filter' => $this->_request->getPost('filter'),
				'start' => $this->_request->getPost('start', 0)
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode("\n", $searchForm->getErrorMessages()));
			}

			if ($point)
			{
				$searchParameters['radius'] = 0.018939;
			}

			$result = (new Application_Model_News)->search(array_merge(
				$searchParameters, ['limit' => 15, 'exclude_id' => $new]
			), $user);

			$response = array('status' => 1);

			// TODO: check performance

			if (count($result))
			{
				foreach ($result as $row)
				{
					$owner = $row->findDependentRowset('Application_Model_User')->current();
					$response['result'][] = array(
						'id' => $row->id,
						'news' => $row->news,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'user' => array(
							'id' => $owner->id,
							'name' => $owner->Name,
							'image' => $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						),
						'html' => My_ViewHelper::render(
							'news/item.html',
							array('user' => $user, 'item' => $row, 'owner' => $owner)
						)
					);
				}
			}
			elseif ($searchParameters['start'] == 0)
			{
				$response['result'] = My_ViewHelper::render('news/empty.html');
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Load friend news action.
	 *
	 * @return	void
	 */
	public function loadFriendNewsAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radius', 0.8),
				'keywords' => $this->_request->getPost('keywords'),
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode("\n", $searchForm->getErrorMessages()));
			}

			$result = (new Application_Model_News)->search(array_merge(
				$searchParameters,
				['filter' => 2, 'start' => 0, 'limit' => 15]
			), $user);

			$response = array('status' => 1);

			if (count($result))
			{
				foreach ($result as $row)
				{
					$owner = $row->findDependentRowset('Application_Model_User')->current();
					$response['result'][] = array(
						'id' => $row->id,
						'news' => $row->news,
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'user' => array(
							'id' => $owner->id,
							'name' => $owner->Name,
							'image' => $owner->getProfileImage($this->view->baseUrl('www/images/img-prof40x40.jpg')),
						)
					);
				}
			}
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'error' => array('message' => $e instanceof RuntimeException ? $e->getMessage() : 'Internal Server Error')
			);
		}

		$this->_helper->json($response);
	}

    public function addNewCommentsAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$data = $this->_request->getParams();

			if (empty($data['news_id']) || !Application_Model_News::checkId($data['news_id'], $news, 0))
			{
				throw new RuntimeException('Incorrect news ID');
			}

			$form = new Application_Form_Comment;

			if (!$form->isValid($data))
			{
				throw new RuntimeException('Validate error', -1);
			}

			$comment = (new Application_Model_Comments)->save($form, $news, $user);

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

		$this->_helper->json($response);
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
					$response['data'][] = My_ViewHelper::render('comment/item.html', array('item' => $comment, 'limit' => 250));
				}

				$count = max($news->comment - ($limitstart + $comentsTable->news_limit), 0);

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

        $this->_helper->json($response);
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

			$form = new Application_Form_Address;
			$data = $this->_request->getPost();

			if (!$form->isValid($data))
			{
				throw new RuntimeException(
					implode("\n", $form->getErrorMessages()));
			}

			$address = $user->findDependentRowset('Application_Model_Address')->current();

			if (!$address)
			{
				$address = (new Application_Model_Address)
					->createRow(['user_id' => $user->id]);
			}

			$address->latitude = $data['latitude'];
			$address->longitude = $data['longitude'];
			$address->street_name = My_ArrayHelper::getProp($data, 'street_name');
			$address->street_number = My_ArrayHelper::getProp($data, 'street_number');
			$address->city = My_ArrayHelper::getProp($data, 'city');
			$address->state = My_ArrayHelper::getProp($data, 'state');
			$address->country = My_ArrayHelper::getProp($data, 'country');
			$address->zip = My_ArrayHelper::getProp($data, 'zip');
			$address->save();

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
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
    }

    public function deleteCommentAction()
	{
        try
		{
			$id = $this->_request->getPost('id');

			if (!Application_Model_Comments::checkId($id, $comment, 0))
			{
				throw new RuntimeException('Incorrect comment ID.');
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

			(new Application_Model_Comments)->deleteRow($comment, $news);

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

		$this->_helper->json($response);
    }

	/**
	 * Vote news action.
	 *
	 * @return void
	 */
	public function voteAction()
	{
		try
		{
			$auth = Zend_Auth::getInstance()->getIdentity();

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$id = $this->_request->getPost('news_id');

			if (!v::intVal()->validate($id))
			{
				throw new RuntimeException('Incorrect post ID value: ' .
					var_export($id, true));
			}

			if (!Application_Model_News::checkId($id, $post, 0))
			{
				throw new RuntimeException('Incorrect post ID', -1);
			}

			$vote = $this->_request->getPost('vote');

			if (!v::intVal()->oneOf(v::equals(-1),v::equals(1))->validate($vote))
			{
				throw new RuntimeException('Incorrect vote value: ' .
					var_export($vote, true), -1);
			}

			$model = new Application_Model_Voting;

			if (!$model->canVote($user, $post))
			{
				throw new RuntimeException('You cannot vote this post', -1);
			}

			$userVote = $model->findVote($post->id, $user->id);

			if (!$user->is_admin && $userVote)
			{
				$userVote->updated_at = (new DateTime)->format(My_Time::$mysqlFormat);
				$userVote->canceled = 1;
				$userVote->save();

				if ($post->vote == 0)
				{
					$lastVote = $model->findVote($post->id);

					if ($lastVote && $lastVote->vote)
					{
						$post->vote = $lastVote->vote;
						$post->save();
					}
				}
				else
				{
					$post->vote = max(0, $post->vote - $userVote->vote);
					$post->save();
				}
			}

			if ($user->is_admin || !$userVote || $userVote->vote != $vote)
			{
				$model->saveVotingData($vote, $user->id, $post);
				$activeVote = $vote;
			}
			else
			{
				$activeVote = 0;
			}

			$response = array(
				'status' => 1,
				'vote' => $post->vote,
				'active' => $activeVote
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? 
					$e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
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
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
	}

	/**
	 * Read more comment action.
	 *
	 * @return void
	 */
	public function readMoreCommentAction()
	{
		try
		{
			if (!Application_Model_Comments::checkId($this->_request->getPost('id'), $comment, 0))
			{
				throw new RuntimeException('Incorrect comment ID', -1);
			}

			$response = array(
				'status' => 1,
				'html' => $comment->renderContent()
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			);
		}

		$this->_helper->json($response);
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
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
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
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
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
		catch (Exception $e)
		{
			$response = array(
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			);
		}

		$this->_helper->json($response);
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
