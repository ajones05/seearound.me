<?php
/**
 * User row model class.
 */
class Application_Model_UserRow extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Returns list of user interests.
	 *
	 * @return	array
	 */
	public function parseInterests()
	{
		$result = array();

		$activities = trim($this->activities());

		if ($activities !== '')
		{
			$interest = explode(',', $activities);

			foreach ($interest as $_interest)
			{
				$_interest = trim($_interest, ' \"\'');
							
				if ($_interest !== '')
				{
					$result[] = $_interest;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns user public profile value.
	 *
	 * @return	integer
	 */
	public function getPublicProfile()
	{
		$profile = $this->findDependentRowset('Application_Model_UserProfile')->current();

		if ($profile)
		{
			return $profile->public_profile;
		}

		return 0;
	}

	/**
	 * Returns user's gender.
	 *
	 * @return	mixed
	 */
	public function gender()
	{
		$profile = $this->findDependentRowset('Application_Model_UserProfile')->current();

		if ($profile)
		{
			return $profile->Gender;
		}

		return null;
	}

	/**
	 * Returns user's activities.
	 *
	 * @return	mixed
	 */
	public function activities()
	{
		$profile = $this->findDependentRowset('Application_Model_UserProfile')->current();

		if ($profile)
		{
			return $profile->Activities;
		}

		return null;
	}

	/**
	 * Updates user token.
	 *
	 * @return	string
	 */
	public function updateToken()
	{
		$token = md5(uniqid($this->Email_id, true));

		(new Application_Model_User)->update(array('Token' => $token), 'id = ' . $this->id);

		return $token;
	}

	/**
	 * Updates user invite count.
	 *
	 * @return	integer
	 */
	public function updateInviteCount()
	{
		$userInvite = $this->findDependentRowset('Application_Model_Invitestatus')->current();

		if (floor((time() - strtotime($userInvite->updated)) / 86400) >= 7)
		{
			$loginStatusModel = new Application_Model_Loginstatus;
			$result = $loginStatusModel->fetchRow(
				$loginStatusModel->select()
					->from($loginStatusModel, 'count(*) as count')
					->where('user_id =?', $this->id)
					->where('login_time>=DATE_SUB(NOW(),INTERVAL 7 DAY)')
			);

			if ($result && $result->count > 5)
			{
				$userInvite->invite_count += floor($result->count / 5);
				$userInvite->updated = new Zend_Db_Expr('NOW()');
				$userInvite->save();
			}
		}

		return $userInvite->invite_count;
	}

	/**
	 * Returns profile image thumb.
	 *
	 * @param	string $thumb "{WIDTH}x{HEIGHT}"
	 * @return	array
	 */
	public function getThumb($thumb)
	{
		return My_Query::getThumb($this, $thumb, 'u', true);
	}

	/**
	 * Returns user timezone.
	 *
	 * @return	DateTimeZone
	 */
	public function getTimezone()
	{
		return (new DateTimeZone($this->timezone ?: 'UTC'));
	}
}

/**
 * User model class.
 */
class Application_Model_User extends Zend_Db_Table_Abstract
{
	/**
	 * @var	Application_Model_UserRow
	 */
	protected static $_auth;

	/**
	 * @var	string
	 */
    protected $_name = 'user_data';

	/**
	 * @var	string
	 */
    protected $_rowClass = 'Application_Model_UserRow';

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
		'Application_Model_UserProfile',
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
		'Profile' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_UserProfile',
			'refColumns' => 'user_id'
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
		My_Query::setThumbsQuery($query, [[26, 26],[55, 55],[320, 320]], 'u');

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
	 * return	mixed	If success Application_Model_UserRow, otherwise NULL
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
	 * Returns auth user.
	 *
	 * return	mixed If success Application_Model_UserRow, otherwise NULL
	 */
	public static function getAuth()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (empty($auth['user_id']))
		{
			return false;
		}

		if (self::$_auth == null)
		{
			self::$_auth = self::findById($auth['user_id']);
		}

		return self::$_auth;
	}

	/**
	 * Finds record by network ID.
	 *
	 * @param	string	$network_id
	 *
	 * return	mixed	If success Application_Model_UserRow, otherwise NULL
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
	 * Finds record by code.
	 *
	 * @param	string	$code
	 *
	 * return	mixed	If success Application_Model_UserRow, otherwise NULL
	 */
	public static function findByCode($code)
	{
		$db = new self;

		$result = $db->fetchRow(
			$db->publicSelect()->where('u.Conf_code=?', $code)
		);

		return $result;
	}

	/**
	 * Finds record by email.
	 *
	 * @param	string	$email
	 *
	 * return	mixed	If success Application_Model_UserRow, otherwise NULL
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
	 * Register a new user.
	 *
	 * @param	array $data
	 * @return	Application_Model_UserRow
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
			'Password' => hash('sha256', $data['password']),
			'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
			'Creation_date' => new Zend_Db_Expr('NOW()'),
			'Conf_code' => My_ArrayHelper::getProp($data, 'Conf_code'),
			'Status' => $data['Status']
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
	 * @param	Facebook\FacebookSession $session
	 * @return	Application_Model_UserRow
	 */
	public function facebookAuthentication(Facebook\FacebookSession $session)
	{
		$me = (new Facebook\FacebookRequest(
		  $session, 'GET', '/me'
		))->execute()->getGraphObject(Facebook\GraphUser::className());

		$email = $me->getEmail();

		if (!$email)
		{
			throw new Exception('Email not activated');
		}

		$network_id = $me->getId();

		$user = $this->findByNetworkId($network_id);

		if (!$user)
		{
			$user = $this->findByEmail($email);

			if ($user)
			{
				$this->update(['Network_id' => $network_id], 'id=' . $user->id);
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
					'Name' => $me->getName(),
					'Email_id' => $email,
					'Status' => 'active',
					'Creation_date'=> new Zend_Db_Expr('NOW()')
				]);

				$picture = (new Facebook\FacebookRequest(
					$session, 'GET', '/me/picture', array('type' => 'large', 'redirect' => false)
				))->execute()->getGraphObject();

				$pictureUrl = $picture->getProperty('url');

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

						$image = (new Application_Model_Image)->save('www/upload/' . $name);

						$thumb26x26 = 'thumb26x26/' . $name;
						$thumb55x55 = 'thumb55x55/' . $name;
						$thumb320x320 = 'uploads/' . $name;

						My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path, [
							[26, 26, ROOT_PATH_WEB . '/' . $thumb26x26, 2],
							[55, 55, ROOT_PATH_WEB . '/' . $thumb55x55, 2],
							[320, 320, ROOT_PATH_WEB . '/' . $thumb320x320]
						]);

						$thumbModel = new Application_Model_ImageThumb;
						$thumbModel->save($thumb26x26, $image, [26, 26]);
						$thumbModel->save($thumb55x55, $image, [55, 55]);
						$thumbModel->save($thumb320x320, $image, [320, 320]);

						$user->image_id = $image->id;
					}
					catch (Exception $e)
					{
					}
				}

				$user->save();

				Application_Model_Profile::getInstance()->insert(array(
					'user_id' => $user->id,
					'Gender' => ucfirst($me->getGender())
				));

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
	 * @param	Application_Model_UserRow	$user
	 * @param	array	$data
	 *
	 * @return	mixed	True on success, Exception on failure.
	 */
	public function updateProfile(Application_Model_UserRow $user, array $data)
	{
		$this->_db->beginTransaction();

		try
		{
			$user_data = array(
				'Name' => $data['name'],
				// 'Email_id' => $data['email']
			);

			if (!empty($data['birth_day']) && !empty($data['birth_month']) && !empty($data['birth_year']))
			{
				$user_data['Birth_date'] = $data['birth_year'] . '-' . $data['birth_month'] . '-' . $data['birth_day'];
			}
			else
			{
				$user_data['Birth_date'] = null;
			}

			$this->update($user_data, $this->_db->quoteInto('id =?', $user->id));

			$address = $user->findDependentRowset('Application_Model_Address')->current();
			$address->latitude = $data['latitude'];
			$address->longitude = $data['longitude'];
			$address->street_name = My_ArrayHelper::getProp($data, 'street_name');
			$address->street_number = My_ArrayHelper::getProp($data, 'street_number');
			$address->city = My_ArrayHelper::getProp($data, 'city');
			$address->state = My_ArrayHelper::getProp($data, 'state');
			$address->country = My_ArrayHelper::getProp($data, 'country');
			$address->zip = My_ArrayHelper::getProp($data, 'zip');

			if ($address->timezone == null && !empty($data['timezone']))
			{
				$address->timezone = $data['timezone'];
			}

			$address->save();

			$profile = $user->findDependentRowset('Application_Model_UserProfile')->current();

			if (!$profile)
			{
				$profile = (new Application_Model_UserProfile)
					->createRow(['user_id' => $user->id]);
			}

			$profile->public_profile = $data['public_profile'];
			$profile->Activities = $data['activities'];
			$profile->Gender = $data['gender'];
			$profile->save();
		
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
}
