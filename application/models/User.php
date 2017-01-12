<?php
/**
 * User model class.
 */
class Application_Model_User extends Zend_Db_Table_Abstract
{
	/**
	 * @var	mixed
	 */
	protected static $_auth;

	/**
	 * @var string
	 */
	public static $imagePath = 'www/upload';

	/**
	 * @var array
	 */
	public static $thumbPath = [
		'26x26' => 'thumb26x26',
		'55x55' => 'thumb55x55',
		'320x320' => 'uploads'
	];

	/**
	 * @var array
	 */
	public static $genderId = [
		'0' => 'Male',
		'1' => 'Female'
	];

	/**
	 * The table name.
	 * @var string
	 */
	 protected $_name = 'user_data';

	/**
	 * @var	array
	 */
    protected $_dependentTables = [
		'Application_Model_Address',
		'Application_Model_UserConfirm',
		'Application_Model_News',
		'Application_Model_Comments',
		'Application_Model_CommentNotify',
		'Application_Model_Conversation',
		'Application_Model_ConversationMessage',
		'Application_Model_Friends'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'Address' => [
			'columns' => 'address_id',
			'refTableClass' => 'Application_Model_Address',
			'refColumns' => 'id'
        ],
		'UserConfirm' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_UserConfirm',
			'refColumns' => 'user_id'
		],
		'News' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'user_id'
		],
		'Comments' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Comments',
			'refColumns' => 'user_id'
		],
		'CommentsNotify' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_CommentNotify',
			'refColumns' => 'user_id'
		],
		'FriendReceiver' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Friends',
			'refColumns' => 'receiver_id'
		],
		'FriendSender' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Friends',
			'refColumns' => 'sender_id'
		]
	];

	/*
     * Returns an instance of a Zend_Db_Table_Select object.
	 *
     * @return Zend_Db_Table_Select
     */
    public function publicSelect()
    {
		$query = parent::select(true)
			->setIntegrityCheck(false)
			->from(['u' => 'user_data'], 'u.*')
			->joinLeft(['a' => 'address'], 'a.id=u.address_id', [
				'address', 'latitude', 'longitude', 'street_name',
				'street_number', 'city', 'state', 'country', 'zip', 'timezone']);

		return $query;
    }

	/**
	 * Checks if user id valid.
	 *
	 * @param	integer	$user_id
	 * @param	mixed	$user
	 *
	 * @return	boolean
	 */
	public static function checkId($user_id, &$user)
	{
		if ($user_id == null)
		{
			return false;
		}

		$user = self::findById($user_id);

		return $user != null;
	}

	/**
	 * Finds record by ID.
	 *
	 * @param integer $id
	 * @param boolean $cache
	 * #return mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findById($id, $loadCache=false)
	{
		if ($loadCache)
		{
			$cache = Zend_Registry::get('cache');
			$user = $cache->load('user_' . $id);

			if ($user != null)
			{
				return $user;
			}
		}

		$model = new self;
		$user = $model->fetchRow($model->publicSelect()->where('u.id=?', $id));

		if ($user != null && $loadCache)
		{
			$cache->save($user->toArray(), 'user_' . $id, ['user' . $id]);
		}

		return $user;
	}

	/**
	 * Finds record by access token.
	 *
	 * @param	integer	$token
	 * @return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findUserByToken($token)
	{
		$result = $this->fetchRow(
			$this->publicSelect()
				->joinLeft(['ul' => 'login_status'], 'u.id=ul.user_id', [
					'login_id' => 'ul.id'
				])
				->where('ul.token=?', $token)
		);
		return $result;
	}

	/**
	 * Returns auth user.
	 *
	 * @param boolean $loadCache
	 * @return mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function getAuth($loadCache=true)
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (empty($auth['user_id']))
		{
			return null;
		}

		if (self::$_auth == null)
		{
			self::$_auth = self::findById($auth['user_id'], $loadCache);
		}

		return self::$_auth;
	}

	/**
	 * Finds record by network ID.
	 *
	 * @param	string	$network_id
	 *
	 * return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findByNetworkId($network_id)
	{
		$db = new self;

		$result = $db->fetchRow(
			$db->publicSelect()->where('u.Network_id=?', $network_id)
		);

		return $result;
	}

	/**
	 * Finds user by registration confirm code.
	 *
	 * @param	string $code
	 * @return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findUserByRegCode($code)
	{
		return $this->fetchRow(
			$this->select()
				->from(['u' => 'user_data'])
				->where('u.status="inactive"')
				->join(['uc' => 'user_confirm'], 'u.id=uc.user_id', '')
				->where('uc.code=?', $code)
				->where('uc.deleted=?', 0)
				->where('uc.type_id=?',
					Application_Model_UserConfirm::$type['registration'])
		);
	}

	/**
	 * Finds user by reset password confirm code.
	 *
	 * @param	string $code
	 * @return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findUserByPassCode($code)
	{
		return $this->fetchRow(
			$this->select()
				->from(['u' => 'user_data'])
				->join(['uc' => 'user_confirm'], 'u.id=uc.user_id', '')
				->where('uc.code=?', $code)
				->where('uc.deleted=?', 0)
				->where('uc.type_id=?',
					Application_Model_UserConfirm::$type['password'])
		);
	}

	/**
	 * Finds record by email.
	 *
	 * @param	string	$email
	 *
	 * return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findByEmail($email)
	{
		$db = new self;
		$result = $db->fetchRow(
			$db->publicSelect()->where('u.Email_id=?', $email)
		);

		return $result;
	}

	/**
	 * Returns encrypted password.
	 *
	 * @param string $password
	 * @return string
	 */
	public static function encryptPassword($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}

	/**
	 * Register a new user.
	 *
	 * @param	array $data
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function register(array $data)
	{
		$addressId = (new Application_Model_Address)->insert([
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
			'street_name' => $data['street_name'],
			'street_number' => $data['street_number'],
			'city' => $data['city'],
			'state' => $data['state'],
			'country' => $data['country'],
			'zip' => $data['zip'],
			'timezone' => $data['timezone']
		]);

		$user = [
			'address_id' => $addressId,
			'Name' => $data['name'],
			'Email_id' => $data['email'],
			'password' => $this->encryptPassword($data['password']),
			'Status' => $data['Status'],
			'image_id' => My_ArrayHelper::getProp($data, 'image_id'),
			'image_name' => My_ArrayHelper::getProp($data, 'image_name'),
			'invite' => 10,
			'Creation_date' => (new DateTime)->format(My_Time::SQL)
		];

		$user['id'] = $this->insert($user);

		return $user;
	}

	/**
	 * User authentication by facebook API.
	 *
	 * @param	Facebook\Facebook $facebookApi
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function facebookAuthentication(Facebook\Facebook $facebookApi)
	{
		$userResponse = $facebookApi->get('/me?fields=id,name,email,gender');
		$userNode = $userResponse->getGraphNode();
		$email = $userNode->getField('email');

		if (!$email)
		{
			throw new Exception('Email not activated');
		}

		$network_id = $userNode->getField('id');

		$user = $this->findByNetworkId($network_id);

		if (!$user)
		{
			$user = $this->findByEmail($email);

			if ($user)
			{
				$this->updateWithCache(['Network_id' => $network_id], $user);
			}
			else
			{
				$geolocation = My_Ip::geolocation();
				$addressData = [
					'latitude' => $geolocation[0],
					'longitude' => $geolocation[1]
				];

				$addressId = (new Application_Model_Address)
					->insert($addressData);

				$user = [
					'address_id' => $addressId,
					'Network_id' => $network_id,
					'Name' => $userNode->getField('name'),
					'Email_id' => $email,
					'Status' => 'active'
				];

				$gender = $userNode->getField('gender');

				if (trim($gender) !== '')
				{
					$user['gender'] = array_search(ucfirst($gender), self::$genderId);
				}

				$pictureResponse = $facebookApi->get('/me/picture?type=large&redirect=false');
				$pictureNode = $pictureResponse->getGraphNode();
				$pictureUrl = $pictureNode->getField('url');

				if (trim($pictureUrl) !== '')
				{
					try
					{
						$ext = strtolower(strtok((new SplFileInfo($pictureUrl))->getExtension(), '?'));

						if (trim($ext) === '')
						{
							throw new Exception('Incorrect file extension');
						}

						do
						{
							$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
							$fullPath = ROOT_PATH_WEB . '/www/upload/' . $name;
						}
						while (file_exists($fullPath));

						if (!@copy($pictureUrl, $fullPath))
						{
							throw new Exception('Failed to copy file ' . $pictureUrl);
						}

						$image = (new Application_Model_Image)->save('www/upload', $name, $thumbs, [
							[[26,26], 'thumb26x26', 2],
							[[55,55], 'thumb55x55', 2],
							[[320,320], 'uploads']
						]);

						$user['image_id'] = $image['id'];
						$user['image_name'] = $name;
					}
					catch (Exception $e)
					{
					}
				}

				$user['id'] = $this->insert($user+[
					'Creation_date'=> new Zend_Db_Expr('NOW()')
				]);

				$user += $addressData;

				$users = Application_Model_Fbtempusers::findAllByNetworkId($network_id);

				if ($users != null)
				{
					$friendModel = new Application_Model_Friends;
					$friendLogModel = new Application_Model_FriendLog;

					foreach($users as $tmpUser)
					{
						$friendId = $friendModel->insert([
							'sender_id' => $tmpUser->sender_id,
							'receiver_id' => $user['id'],
							'status' => $friendModel->status['confirmed'],
							'source' => 'herespy'
						]);

						$friendLogModel->insert([
							'friend_id' => $friendId,
							'user_id' => $user['id'],
							'status_id' => $friendModel->status['confirmed']
						]);

						$tmpUser->delete();
					}
				}
			}
		}

		return $user;
	}

	/**
	 * Update the user data.
	 *
	 * @param mixed $user
	 * @param array $data
	 * @return mixed True on success, Exception on failure.
	 * @throws Exception
	 */
	public function updateProfile($user, array $data)
	{
		$this->_db->beginTransaction();

		try
		{
			$user_data = [
				'Name' => $data['name'],
				'Email_id' => $data['email'],
				'public_profile' => $data['public_profile'],
				'gender' => $data['gender'],
				'interest' => $this->filterInterest($data['interest'])
			];

			if (!empty($data['birth_day']) && !empty($data['birth_month']) && !empty($data['birth_year']))
			{
				$user_data['Birth_date'] = $data['birth_year'] . '-' . $data['birth_month'] . '-' . $data['birth_day'];
			}
			else
			{
				$user_data['Birth_date'] = null;
			}

			$this->updateWithCache($user_data, $user);

			(new Application_Model_Address)->update([
				'address' => null,
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
				'street_name' => My_ArrayHelper::getProp($data, 'street_name'),
				'street_number' => My_ArrayHelper::getProp($data, 'street_number'),
				'city' => My_ArrayHelper::getProp($data, 'city'),
				'state' => My_ArrayHelper::getProp($data, 'state'),
				'country' => My_ArrayHelper::getProp($data, 'country'),
				'zip' => My_ArrayHelper::getProp($data, 'zip'),
				'timezone' => My_ArrayHelper::getProp($data, 'timezone', $user['timezone'])
			], 'id=' . $user['address_id']);

			$this->_db->commit();
		}
		catch (Exception $e)
		{
			$this->_db->rollBack();

			throw $e;
		}

		return true;
	}

	/**
	 * Updates user invite count.
	 *
	 * @param array|Zend_Db_Table_Row_Abstract $user
	 * @return boolean
	 */
	public static function updateInvites($user)
	{
		if (date('N') != 1)
		{
			return false;
		}

		if (!empty($user['invite_updated_at']) &&
			floor((time() - strtotime($user['invite_updated_at'])) / 86400) >= 7)
		{
			$loginModel = new Application_Model_Loginstatus;
			$result = $loginModel->fetchRow(
				$loginModel->select()
					->from($loginModel, 'count(*) as count')
					->where('user_id=?', $user['id'])
					->where('login_time>=DATE_SUB(NOW(),INTERVAL 7 DAY)')
			);

			if ($result && $result->count > 5)
			{
				(new self)->updateWithCache([
					'invite' => $user['invite'] + floor($result->count / 5),
					'invite_updated_at' => new Zend_Db_Expr('NOW()')
				], $user);
			}
		}

		return true;
	}

	/**
	 * Filters user interest data.
	 *
	 * @param	string $interests
	 * @return string
	 */
	public function filterInterest($interests)
	{
		if (trim($interests) !== '')
		{
			$result = [];

			foreach (explode(',', $interests) as $interest)
			{
				$interest = trim($interest);

				if ($interest !== '')
				{
					$result[] = $interest;
				}
			}

			if ($result != null)
			{
				return implode(', ', $result);
			}
		}

		return null;
	}

	/**
	 * Returns user karma.
	 *
	 * @param mixed $user
	 * @return array
	 */
	public static function getKarma($user)
	{
		$posts = My_ArrayHelper::getProp($user, 'post', 0);
		$votes = My_ArrayHelper::getProp($user, 'vote', 0);
		$comments = My_ArrayHelper::getProp($user, 'comment', 0);
		$comments_other = My_ArrayHelper::getProp($user, 'comment_other', 0);

		return round($posts + (.25 * ($votes + $comments)) +
				$comments_other * .25, 4);
	}

	/**
	 * Returnds user thumbail path.
	 *
	 * @param mixed $row
	 * @param string $thumb Thumbnail dimensions WIDTHxHEIGHT
	 * @param array $options
	 * @return mixed String on success, otherwise NULL
	 */
	public static function getThumb($row, $thumb, array $options=[])
	{
		$imageField = My_ArrayHelper::getProp($options, 'alias') . 'image_name';

		if (!empty($row[$imageField]))
		{
			return self::$thumbPath[$thumb] . '/' . $row[$imageField];
		}

		if (!array_key_exists('default', $options) || $options['default'])
		{
			return self::getDefaultThumb($thumb);
		}

		return null;
	}

	/**
	 * Returnds user default path.
	 *
	 * @param string $thumb Thumbnail dimensions WIDTHxHEIGHT
	 * @return sString
	 */
	public static function getDefaultThumb($thumb)
	{
		return self::$thumbPath[$thumb] . '/default.jpg';
	}

	/**
	 * Returns user gender label.
	 *
	 * @param mixed $row
	 * @param string $alias
	 * @return string
	 */
	public static function getGender($row, $alias='')
	{
		$gender = My_ArrayHelper::getProp($row, $alias.'gender');
		return $gender !== null ? self::$genderId[$gender] : '';
	}

	/**
	 * Returns user timezone.
	 *
	 * @param nixed $user
	 * @return DateTimeZone
	 */
	public static function getTimezone($user)
	{
		return (new DateTimeZone($user['timezone'] ?: 'UTC'));
	}

	/**
	 * Updates existing rows.
	 *
	 * @param array $data
	 * @param mixed $user
	 * @return integer
	 */
	public function updateWithCache(array $data, $user)
	{
		$result = $this->update($data, 'id=' . $user['id']);
		$this->cleanUserCache($user);
		return $result;
	}

	/**
	 * Clears user date cache.
	 *
	 * @param array|Zend_Db_Table_Row_Abstract $user
	 * @return integer
	 */
	public static function cleanUserCache($user)
	{
		Zend_Registry::get('cache')->clean(
			Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
			['user' . $user['id']]
		);
	}

	/**
	 * Returns user first namr.
	 *
	 * @param array|Zend_Db_Table_Row_Abstract $user
	 * @return string
	 */
	public static function getFirstName($row, $alias='')
	{
		$name = My_ArrayHelper::getProp($row, $alias.'name');
		return My_ArrayHelper::getProp(explode(' ', $name), 0);
	}
}
