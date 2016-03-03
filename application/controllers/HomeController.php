<?php
use Respect\Validation\Validator as v;

class HomeController extends Zend_Controller_Action
{
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
			(new Zend_Session_Namespace('userData'))->unsetAll();

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
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places');

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

			if (!$upload->isValid('image'))
			{
				throw new RuntimeException(implode('. ', $upload->getMessages()), -1);
			}

			$ext = My_CommonUtils::$mimetype_extension[$upload->getMimeType('image')];

			do
			{
				$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
				$full_path = ROOT_PATH_WEB . '/www/upload/' . $name;
			}
			while (file_exists($full_path));

			$upload->addFilter('Rename', $full_path);
			$upload->receive();

			if ($user->image_id)
			{
				$user->findDependentRowset('Application_Model_Image')
					->current()->deleteImage();
			}

			$image = (new Application_Model_Image)->save('www/upload/' . $name);

			$thumb55x55 = 'thumb55x55/' . $name;
			$thumb24x24 = 'thumb24x24/' . $name;
			$thumb320x320 = 'uploads/' . $name;

			My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path, [
				[24, 24, ROOT_PATH_WEB . '/' . $thumb24x24],
				[55, 55, ROOT_PATH_WEB . '/' . $thumb55x55],
				[320, 320, ROOT_PATH_WEB . '/' . $thumb320x320]
			]);

			$thumbModel = new Application_Model_ImageThumb;
			$thumbModel->save($thumb24x24, $image, [24, 24]);
			$thumb = $thumbModel->save($thumb55x55, $image, [55, 55]);
			$thumbModel->save($thumb320x320, $image, [320, 320]);

			$user->image_id = $image->id;
			$user->save();

			$response = [
				'status' => 1,
				'url' => $this->view->baseUrl($thumb->path)
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ? $e->getMessage() :
					'Internal Server Error'
			];
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
				->where('news.user_id=?', $profile->id)
				->order('news.id DESC')
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

			if (!Application_Model_User::checkId($auth['user_id'], $user))
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$form = new Application_Form_Address;

			if (!$form->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(
					implode("\n", $form->getErrorMessages()));
			}

			$address = $user->findDependentRowset('Application_Model_Address')->current();
			$address->setFromArray($form->getValues());
			$address->save();

			$response = ['status' => 1];
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
