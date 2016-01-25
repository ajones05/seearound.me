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
	 * Returns user profile image.
	 *
	 * @return	array
	 */
	public function getProfileImage($default)
	{
		$userImage = $this->findManyToManyRowset('Application_Model_Image',
			'Application_Model_UserImage')->current();

		if ($userImage)
		{
			return Zend_Controller_Front::getInstance()->getBaseUrl() . '/' .
				$userImage->findThumb(array(320, 320))->path;
		}

		return $default;
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
					->where('login_time >= "' . (new DateTime('-1 week'))->format(DateTime::W3C) . '"')
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
}

/**
 * User model class.
 */
class Application_Model_User extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
    protected $_name     = 'user_data';

	/**
	 * @var	string
	 */
    protected $_primary  = array('id');

	/**
	 * @var	string
	 */
    protected $_rowClass = 'Application_Model_UserRow';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_News',
		'Application_Model_Comments',
		'Application_Model_CommentNotify',
		'Application_Model_Address',
		'Application_Model_Conversation',
		'Application_Model_ConversationMessage',
		'Application_Model_Friends',
		'Application_Model_UserProfile',
		'Application_Model_Invitestatus'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'News' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'user_id'
		),
		'Comments' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Comments',
			'refColumns' => 'user_id'
		),
		'CommentsNotify' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_CommentNotify',
			'refColumns' => 'user_id'
		),
		'FriendReceiver' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Friends',
			'refColumns' => 'reciever_id'
		),
		'FriendSender' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Friends',
			'refColumns' => 'sender_id'
		),
		'Profile' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_UserProfile',
			'refColumns' => 'user_id'
		),
		'InviteStatus' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Invitestatus',
			'refColumns' => 'user_id'
		)
	); 

    public function getUsers($data = array(), $all = false) {

        $select = $this->select();

        if(count($data) > 0) {

            foreach($data as $index => $value) {

                $select->where($index. " =?", $value);

            }

        }

        if($all) {

            return $this->fetchAll($select);

        }else {

            return $this->fetchRow($select);

        }

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
		$db = new self;
		$result = $db->fetchRow(
			$db->select()->where('user_data.id =?', $id)
		);

		return $result;
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
			$db->select()->where('user_data.Network_id =?', $network_id)
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
			$db->select()->where('Conf_code=?', $code)
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
			$db->select()->where('Email_id =?', $email)
		);

		return $result;
	}

	/**
	 * Register a new user.
	 *
	 * @param	array	$data
	 *
	 * @return	Application_Model_UserRow
	 */
	public function register(array $data)
	{
		$user = $this->createRow(array(
			'Name' => $data['name'],
			'Email_id' => $data['email'],
			'Password' => hash('sha256', $data['password']),
			'Creation_date' => date('Y-m-d H:i'),
			'Update_date' => date('Y-m-d H:i'),
			'Conf_code' => My_ArrayHelper::getProp($data, 'Conf_code', ''),
			'Status' => $data['Status']
		));

		$user->id = $user->save();

		(new Application_Model_Invitestatus)->insert(array(
			'user_id' => $user->id,
			'invite_count' => 10,
			'created' => new Zend_Db_Expr('NOW()'),
			'updated' => new Zend_Db_Expr('NOW()')
		));

		$addressModel = new Application_Model_Address;
		$addressModel->insert(array(
			'user_id' => $user->id,
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
			'street_name' => My_ArrayHelper::getProp($data, 'street_name'),
			'street_number' => My_ArrayHelper::getProp($data, 'street_number'),
			'city' => My_ArrayHelper::getProp($data, 'city'),
			'state' => My_ArrayHelper::getProp($data, 'state'),
			'country' => My_ArrayHelper::getProp($data, 'country'),
			'zip' => My_ArrayHelper::getProp($data, 'zip')
		));

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
				$this->update(array('Network_id' => $network_id), 'id=' . $user->id);
			}
			else
			{
				$user = $this->createRow(array(
					'Network_id' => $network_id,
					'Name' => $me->getName(),
					'Email_id' => $email,
					'Status' => 'active',
					'Creation_date'=> new Zend_Db_Expr('NOW()'),
					'Update_date' => new Zend_Db_Expr('NOW()')
				));
				$user->save();

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
							$fullPath = ROOT_PATH . '/www/upload/' . $name;
						}
						while (file_exists($fullPath));

						if (!@copy($pictureUrl, $fullPath))
						{
							throw new Exception('Failed to copy file ' . $pictureUrl);
						}

						$image = (new Application_Model_Image)->save('www/upload/' . $name);

						(new Application_Model_UserImage)->insert(array(
							'user_id' => $user->id,
							'image_id' => $image->id
						));

						$thumb320x320 = 'uploads/' . $name;

						My_CommonUtils::createThumbs(ROOT_PATH . '/' . $image->path, array(
							array(320, 320, ROOT_PATH . '/' . $thumb320x320)
						));

						(new Application_Model_ImageThumb)
							->save($thumb320x320, $image, array(320, 320));

						$user->save();
					}
					catch (Exception $e)
					{
					}
				}

				Application_Model_Profile::getInstance()->insert(array(
					'user_id' => $user->id,
					'Gender' => ucfirst($me->getGender())
				));

				$geolocation = My_Ip::geolocation();

				(new Application_Model_Address)->insert(array(
					'user_id' => $user->id,
					'latitude' => $geolocation[0],
					'longitude' => $geolocation[1]
				));

				(new Application_Model_Invitestatus)->insert(array(
					'user_id' => $user->id,
					'created' => new Zend_Db_Expr('NOW()'),
					'updated' => new Zend_Db_Expr('NOW()')
				));

				$users = Application_Model_Fbtempusers::getInstance()->findAllByNetworkId($network_id);

				if (count($users))
				{
					$friendsModel = new Application_Model_Friends;

					foreach($users as $tmp_user)
					{
						$friendStatus = $friendsModel->createRow(array(
							'sender_id' => $tmp_user->sender_id,
							'reciever_id' => $user->id,
							'status' => $friendsModel->status['confirmed'],
							'source' => 'herespy'
						));
						$friendStatus->save();

						(new Application_Model_FriendLog)->insert(array(
							'friend_id' => $friendStatus->id,
							'user_id' => $user->id,
							'status_id' => $friendStatus->status
						));

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

			$profile = $user->findDependentRowset('Application_Model_UserProfile')->current();
			
			if (!$profile)
			{
				$profile = (new Application_Model_UserProfile)->createRow(array('user_id' => $user->id));
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
}
