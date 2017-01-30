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

			$statusModel = new Application_Model_Loginstatus;
			$loginStatus = $statusModel->findById($data['login_id']);

			if ($loginStatus != null)
			{
				$statusModel->update([
					'logout_time' => new Zend_Db_Expr('NOW()')
				], 'id=' . $data['login_id']);
			}

			$auth->clearIdentity();
			Zend_Session::forgetMe();
		}

		$this->_redirect('/');
	}

	/**
	 * Edit profile action.
	 *
	 * @return void
	 */
	public function editProfileAction()
	{
		$userModel = new Application_Model_User;
		$user = $userModel->getAuth();

		if ($user == null)
		{
			throw new RuntimeException('You are not authorized to access this action');
		}

		$settings = Application_Model_Setting::getInstance();
		$profileForm = new Application_Form_Profile;

		if ($this->_request->isPost())
		{
			$data = $this->_request->getPost();

			if ($profileForm->isValid($data))
			{
				$userModel->updateProfile($user, $data);
				$this->_redirect('profile');
			}

			if (!$profileForm->latitude->hasErrors() &&
				!$profileForm->longitude->hasErrors())
			{
				$this->view->headScript('script', 'var postData=' . json_encode([
					'address' => Application_Model_Address::format($data),
					'latitude' => $profileForm->latitude->getValue(),
					'longitude' => $profileForm->longitude->getValue()
				]) . ';');
			}
		}
		else
		{
			$profileForm->setDefaults([
				'email' => $user['Email_id'],
				'public_profile' => $user['public_profile'],
				'name' => $user['Name'],
				'gender' => $user['gender'],
				'interest' => $user['interest'],
				'latitude' => $user['latitude'],
				'longitude' => $user['longitude'],
				'street_name' => $user['street_name'],
				'street_number' => $user['street_number'],
				'city' => $user['city'],
				'state' => $user['state'],
				'country' => $user['country'],
				'zip' => $user['zip'],
				'timezone' => $user['timezone']
			]);

			if (!empty($user['Birth_date']))
			{
				$birthday = new DateTime($user['Birth_date']);
				$profileForm->setDefaults([
					'birth_day' => $birthday->format('d'),
					'birth_month' => $birthday->format('m'),
					'birth_year' => $birthday->format('Y')
				]);
			}
		}

		$addressFormat = Application_Model_Address::format($user);
		$this->view->addressFormat = $addressFormat;

		$this->view->headScript()
			->appendScript('var profileData=' . json_encode([
				'address' => $addressFormat,
				'latitude' => $user['latitude'],
				'longitude' => $user['longitude'],
				'timezone' => $user['timezone']
			]) . ',' .
			'timizoneList=' . json_encode(My_CommonUtils::$timezone) . ';')
			->prependFile('https://maps.googleapis.com/maps/api/js?v=3&libraries=places&key=' .
				$settings['google_mapsKey']);

		$this->view->profileForm = $profileForm;
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
		$user = Application_Model_User::getAuth();
		$user_id = $this->_request->getParam('user_id');

		if (!v::optional(v::intVal())->validate($user_id))
		{
			throw new RuntimeException('Incorrect user ID value: ' .
				var_export($user_id, true));
		}

		$auth_id = My_ArrayHelper::getProp($user, 'id');

		if ($user_id != null && $user_id !== $auth_id)
		{
			$profile = Application_Model_User::findById($user_id, true);

			if ($profile == null)
			{
				throw new RuntimeException('Incorrect user ID: ' .
					var_export($user_id, true));
			}
		}
		else
		{
			if ($auth_id == null)
			{
				throw new RuntimeException('You are not authorized to access this action');
			}

			$profile = $user;
		}

		$addressFormat = Application_Model_Address::format($profile,
			['street' => false]);

		$this->view->searchForm = new Application_Form_PostSearch;
		$this->view->user = $user;
		$this->view->auth_id = $auth_id;
		$this->view->profile = $profile;
		$this->view->addressFormat = $addressFormat;

		$location = $user != null ? [$user['latitude'], $user['longitude']] :
			My_Ip::geolocation();

		$this->view->appendScript = [
			'opts=' . json_encode(['lat' => $location[0], 'lng' => $location[1]]),
			'profile=' . json_encode(['id' => $profile['id']])
		];

		if ($user != null)
		{
			if ($user['id'] != $profile['id'])
			{
				$isFriend = (new Application_Model_Friends)->isFriend($user, $profile);
				$this->view->appendScript[] = 'isFriend=' .
					($isFriend != null ? 'true' : 'false');
				$this->view->isFriend = $isFriend;
			}

			$this->view->appendScript[] = 'user=' . json_encode([
				'name' => $user['Name'],
				'image' => $this->view->baseUrl(
					Application_Model_User::getThumb($user, '55x55')),
				'location' => [$user['latitude'], $user['longitude']],
				'is_admin' => $user['is_admin']
			]);
		}
		else
		{
			$this->view->appendScript[] = 'user=' . json_encode([
				'location' => $location
			]);
		}

		$posts = (new Application_Model_News)->search([
			'latitude' => $location[0],
			'longitude' => $location[1],
			'limit' => 15,
			'radius' => 1.5,
			'filter' => 0
		], ['id' => $profile['id']], [
			'link' => ['thumbs'=>[[448,320]]],
			'thumbs' => [[448,320],[960,960]]
		]);

		if ($posts->count())
		{
			$data = [];

			foreach ($posts as $post)
			{
				$data[$post->id] = [
					'id' => $post['id'],
					'user_id' => $post['user_id'],
					'lat' => $post['latitude'],
					'lng' => $post['longitude']
				];
			}

			$this->view->posts = $posts;
			$this->view->appendScript[] = 'postData=' . json_encode($data);
		}

		$this->view->viewPage = 'profile';
		$this->view->layout()->setLayout('map');
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
