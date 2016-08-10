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
				$status->logout_time = new Zend_Db_Expr('NOW()');
				$status->save();
			}

			$auth->clearIdentity();
			Zend_Session::forgetMe();
		}

		$this->_redirect($this->view->baseUrl('/login'));
	}

	/**
	 * Edit profile action.
	 *
	 * @return void
	 */
	public function editProfileAction()
	{
		$config = Zend_Registry::get('config_global');
		$userModel = new Application_Model_User;
		$user = $userModel->getAuth();

		if ($user == null)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		$settings = Application_Model_Setting::getInstance();
		$profileForm = new Application_Form_Profile;
		$addressModel = new Application_Model_Address;
		$addressForm = new Application_Form_Address;

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
			$addressForm->setDefaults([
				'latitude' => $user->latitude,
				'longitude' => $user->longitude,
				'street_name' => $user->street_name,
				'street_number' => $user->street_number,
				'city' => $user->city,
				'state' => $user->state,
				'country' => $user->country,
				'zip' => $user->zip
			]);
			$profileForm->setDefaults([
				'email' => $user->Email_id,
				'public_profile' => $user->public_profile,
				'name' => $user->Name,
				'gender' => $user->gender,
				'activities' => $user->activity,
				'latitude' => $user->latitude,
				'longitude' => $user->longitude,
				'timezone' => $user->timezone,
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

		$addressFormat = $addressModel->format($user);
		$this->view->addressFormat = $addressFormat;

		$this->view->headScript()
			->appendScript('var profileData=' . json_encode([
				'address' => $addressFormat,
				'latitude' => $user->latitude,
				'longitude' => $user->longitude,
				'timezone' => $user->timezone
			]) . ',' .
			'timizoneList=' . json_encode(My_CommonUtils::$timezone) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3&libraries=places&key=' .
				$settings['google_mapsKey']);

		$this->view->profileForm = $profileForm;
		$this->view->addressForm = $addressForm;
		$this->view->user = $user;
		$this->view->changeLocation = true;
	}

	public function imageUploadAction()
	{
		try
		{
			$userModel = new Application_Model_User;
			$user = $userModel->getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$upload = new Zend_File_Transfer;
			$upload->setValidators(array(
				array('Extension', false, array('jpg', 'jpeg', 'png', 'gif')),
				array('MimeType', false, array('image/jpeg', 'image/png', 'image/gif'),
					array('magicFile' => false)),
				array('Count', false, 1)
			));

			if (!$upload->isValid('image'))
			{
				throw new RuntimeException(implode('. ', $upload->getMessages()), -1);
			}

			if (!empty($user['image_id']))
			{
				$db = Zend_Db_Table::getDefaultAdapter();

				foreach ($userModel::$thumbPath as $path)
				{
					@unlink(ROOT_PATH_WEB . '/' . $path . '/' . $user['image_name']);
				}

				$db->delete('image_thumb', 'image_id=' . $user['image_id']);

				@unlink(ROOT_PATH_WEB . '/' . $userModel->imagePath . '/' .
					$user['image_name']);

				$db->delete('image', 'id=' . $user['image_id']);
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

			$image = (new Application_Model_Image)->save('www/upload', $name, $thumbs, [
				[[26,26], 'thumb26x26', 2],
				[[55,55], 'thumb55x55', 2],
				[[320,320], 'uploads']
			]);

			$userModel->updateWithCache([
				'image_id' => $image['id'],
				'image_name' => $name
			], $user);

			$response = [
				'status' => 1,
				'url' => $this->view->baseUrl('thumb55x55/' . $name)
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
		$userModel = new Application_Model_User;
		$isAuth = Zend_Auth::getInstance()->hasIdentity();

		if ($isAuth)
		{
			$user = $userModel->getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$this->view->user = $user;
		}

		$settings = Application_Model_Setting::getInstance();
		$user_id = $this->_request->getParam('user');

		if (!v::optional(v::intVal())->validate($user_id))
		{
			throw new RuntimeException('Incorrect user ID value: ' .
				var_export($user_id, true));
		}

		if ($user_id)
		{
			if (!$userModel->checkId($user_id, $profile))
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}
		}
		else
		{
			if (!$isAuth)
			{
				throw new RuntimeException('You are not authorized to access this action', -1);
			}

			$profile = $user;
		}

		$this->view->auth_id = $isAuth ? $user->id : null;
		$this->view->profile = $profile;

		if ($isAuth && $user->id != $profile->id)
		{
			$isFriend = (new Application_Model_Friends)->isFriend($user, $profile);
			$this->view->headScript()
				->appendScript('var isFriend=' . ($isFriend ? 'true' : 'false') . ';');
			$this->view->isFriend = $isFriend;
		}

		$this->view->headLink()
			->appendStylesheet(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.css'));

		$addressFormat = Application_Model_Address::format($profile, ['street'=>false]);
		$this->view->addressFormat = $addressFormat;

		$this->view->headScript()
			->appendScript('var reciever_userid=' . json_encode($profile->id) . ',' .
				'profileData=' . json_encode([
				'id' => $profile->id,
				'address' => $addressFormat,
				'latitude' => $profile->latitude,
				'longitude' => $profile->longitude
			]) . ';')
			->appendFile(My_Layout::assetUrl('bower_components/jquery-loadmask/src/jquery.loadmask.js'));

		My_Layout::appendAsyncScript('//maps.googleapis.com/maps/api/js?' .
				'key=' . $settings['google_mapsKey'] . '&v=3&callback=initMap', $this->view);
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
			$user = Application_Model_User::getAuth();

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$searchForm = new Application_Form_PostSearch;
			$searchParameters = [
				'latitude' => $this->_request->getPost('latitude'),
				'longitude' => $this->_request->getPost('longitude'),
				'radius' => $this->_request->getPost('radius', 1.5),
				'keywords' => $this->_request->getPost('keywords'),
			];

			if (!$searchForm->validateSearch($searchParameters))
			{
				throw new RuntimeException(
					implode("\n", $searchForm->getErrorMessages()));
			}

			$result = (new Application_Model_News)->search($searchParameters +
				['filter' => 2, 'start' => 0, 'limit' => 15], $user);

			$response = ['status' => 1];

			if (count($result))
			{
				foreach ($result as $row)
				{
					$ownerThumb = Application_Model_User::getThumb($row, '55x55',
						['alias' => 'owner_']);
					$response['result'][] = [
						'id' => $row->id,
						'news' => My_StringHelper::stringLimit($row->news, 100, '...'),
						'latitude' => $row->latitude,
						'longitude' => $row->longitude,
						'user' => [
							'id' => $row->user_id,
							'name' => $row->owner_name,
							'image' => $this->view->baseUrl($ownerThumb),
						]
					];
				}
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => 0,
				'message' => $e instanceof RuntimeException ?
					$e->getMessage() : 'Internal Server Error'
			];
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
			$user = Application_Model_User::getAuth(true);

			if ($user == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$addressForm = new Application_Form_Address;

			if (!$addressForm->isValid($this->_request->getPost()))
			{
				throw new RuntimeException(
					implode("\n", $addressForm->getErrorMessages()));
			}

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->update('address', $addressForm->getValues(),
				'id=' . $user['address_id']);

			Application_Model_User::cleanUserCache($user);

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
			$code = $this->_request->getParam('code');

			if (!v::stringType()->validate($code))
			{
				throw new RuntimeException('Incorrect confirm code value: ' .
					var_export($code, true));
			}

			$userModel = new Application_Model_User;
			$user = $userModel->findUserByRegCode($code);

			if ($user == null)
			{
				throw new RuntimeException('Incorrect confirm code: ' .
					var_export($code, true));
			}

			$userModel->update(['Status' => 'active'], 'id=' . $user['id']);
			$this->view->success = 'Email confirm success';
		}
		catch (RuntimeException $e)
		{
			$this->view->errors = "Inactive link";
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}
}
