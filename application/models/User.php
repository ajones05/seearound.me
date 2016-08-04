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
	 * @var	string
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
		'Application_Model_Friends',
		'Application_Model_Invitestatus'
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
			'refColumns' => 'reciever_id'
		],
		'FriendSender' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Friends',
			'refColumns' => 'sender_id'
		],
		'InviteStatus' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Invitestatus',
			'refColumns' => 'user_id'
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

    public function recordForEmail($sender = null, $reciever = null, $isFb = false) 

    {

        $result = new stdClass();

        if($sender && $reciever) {

            if($isFb) {

                $select = $this->select()

                        ->where('id =?', $sender);

                if($rows = $this->fetchRow($select)) {

                    $result->senderName = $row->Name;

                    $result->senderEmail = $row->Email_id;

                }

                $select = $this->select()

                        ->where('Network_id =?', $reciever);                        

                if($rows = $this->fetchRow($select)) {

                    $result->recieverName = $row->Name;

                    $result->recieverEmail = $row->Email_id;

                } 

            } else {

                $select = $this->select()

                        ->where('id =?', $sender);

                if($rows = $this->fetchRow($select)) {

                    $result->senderName = $rows->Name;

                    $result->senderEmail = $rows->Email_id;

                }

                $select = $this->select()

                        ->where('id =?', $reciever);                        

                if($rows = $this->fetchRow($select)) {

                    $result->recieverName = $rows->Name;

                    $result->recieverEmail = $rows->Email_id;

                }           

            } 

        }

        return $result;

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
	 * @param	integer	$id
	 *
	 * return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findById($id)
	{
		$model = new self;
		$result = $model->fetchRow(
			$model->publicSelect()->where('u.id=?', $id)
		);
		return $result;
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
	 * return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function getAuth($loadCache=false)
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (empty($auth['user_id']))
		{
			return false;
		}

		if (self::$_auth == null)
		{
			if (!$loadCache)
			{
				self::$_auth = self::findById($auth['user_id']);
				return self::$_auth;
			}

			$cache = Zend_Registry::get('cache');
			$user = $cache->load('user_' . $auth['user_id']);

			if ($user == null)
			{
				$user = self::findById($auth['user_id']);

				if ($user == null)
				{
					return false;
				}

				$cache->save($user->toArray(), 'user_' . $auth['user_id']);
			}

			self::$_auth = $user;
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
		$address = (new Application_Model_Address)->createRow([
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
			'street_name' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'street_name')),
			'street_number' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'street_number')),
			'city' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'city')),
			'state' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'state')),
			'country' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'country')),
			'zip' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'zip')),
			'timezone' => My_StringHelper::trimToNull(
				My_ArrayHelper::getProp($data, 'timezone'))
		]);
		$address->save();

		$user = $this->createRow([
			'address_id' => $address->id,
			'Name' => $data['name'],
			'Email_id' => $data['email'],
			'password' => $this->encryptPassword($data['password']),
			'Creation_date' => new Zend_Db_Expr('NOW()'),
			'Status' => $data['Status'],
			'image_id' => My_ArrayHelper::getProp($data, 'image_id')
		]);
		$user->save();

		(new Application_Model_Invitestatus)->insert([
			'user_id' => $user->id,
			'invite_count' => 10,
			'created' => new Zend_Db_Expr('NOW()')
		]);

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
				$this->update(['Network_id' => $network_id], 'id=' . $user->id);
				Zend_Registry::get('cache')->remove('user_' . $user['id']);
			}
			else
			{
				$geolocation = My_Ip::geolocation();

				$address = (new Application_Model_Address)->createRow([
					'latitude' => $geolocation[0],
					'longitude' => $geolocation[1]
				]);
				$address->save();

				$user = $this->createRow([
					'address_id' => $address->id,
					'Network_id' => $network_id,
					'Name' => $userNode->getField('name'),
					'Email_id' => $email,
					'Status' => 'active',
					'Creation_date'=> new Zend_Db_Expr('NOW()')
				]);

				$gender = $userNode->getField('gender');

				if (trim($gender) !== '')
				{
					$user->gender = array_search(ucfirst($gender), self::$genderId);
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

						$image = (new Application_Model_Image)->save('www/upload', $name, [
							[[26,26], 'thumb26x26', 2],
							[[55,55], 'thumb55x55', 2],
							[[320,320], 'uploads']
						]);

						$user->image_id = $image->id;
						$user->image_name = $name;
					}
					catch (Exception $e)
					{
					}
				}

				$user->save();

				(new Application_Model_Invitestatus)->insert([
					'user_id' => $user->id,
					'created' => new Zend_Db_Expr('NOW()')
				]);

				$users = Application_Model_Fbtempusers::getInstance()->findAllByNetworkId($network_id);

				if (count($users))
				{
					$friendsModel = new Application_Model_Friends;

					foreach($users as $tmp_user)
					{
						$friendsModel->createRow([
							'sender_id' => $tmp_user->sender_id,
							'reciever_id' => $user->id,
							'status' => $friendsModel->status['confirmed'],
							'source' => 'herespy'
						])->updateStatus($user);

						$tmp_user->delete();
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
				'activity' => $this->filterActivity($data['activities'])
			];

			if (!empty($data['birth_day']) && !empty($data['birth_month']) && !empty($data['birth_year']))
			{
				$user_data['Birth_date'] = $data['birth_year'] . '-' . $data['birth_month'] . '-' . $data['birth_day'];
			}
			else
			{
				$user_data['Birth_date'] = null;
			}

			$this->update($user_data, 'id=' . $user['id']);

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

			Zend_Registry::get('cache')->remove('user_' . $user['id']);

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
	 * Filters user activity data.
	 *
	 * @param	string $activities
	 * @return string
	 */
	public function filterActivity($activities)
	{
		if (trim($activities) !== '')
		{
			$result = [];

			foreach (explode(',', $activities) as $activity)
			{
				$activity = trim($activity);

				if ($activity !== '')
				{
					$result[] = $activity;
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
	 * @param	integer $user_id
	 * @return	array
	 */
	public function getKarma($user_id)
	{
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();

		$selfStasts = $db->fetchRow($db->select()
			->from(['n' => 'news'], [
				'COUNT(n.id) AS post',
				'IFNULL(SUM(n.comment), 0) as comment',
				'IFNULL(SUM(n.vote), 0) as vote'
			])
			->where('n.isdeleted=0 AND n.user_id=' . $user_id)
		);

		$otherStats = $db->fetchRow($db->select()
			->from(['c' => 'comments'], ['count(c.id) AS count'])
			->where('c.isdeleted=0 AND c.user_id=' . $user_id)
			->joinLeft(['n' => 'news'], 'n.id=c.news_id', '')
			->where('n.user_id<>' . $user_id)
		);

		return [
			'post' => $selfStasts['post'],
			'comment' => $selfStasts['comment'],
			'comment_other' => $otherStats['count'],
			'vote' => $selfStasts['vote'],
			'karma' => $selfStasts['post'] +
				(.25 * ($selfStasts['vote'] + $selfStasts['comment'])) +
				$otherStats['count'] * .25
		];
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
			$imageName = $row[$imageField];
		}
		elseif (!array_key_exists('default', $options) || $options['default'])
		{
			$imageName = 'default.jpg';
		}
		else
		{
			return null;
		}

		return self::$thumbPath[$thumb] . '/' . $imageName;
	}

	/**
	 * Returns user gender label.
	 *
	 * @param mixed $row
	 * @return string
	 */
	public static function getGender($row)
	{
		return $row['gender'] !== null ? self::$genderId[$row['gender']] : '';
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
}
