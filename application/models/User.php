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

		$activities = trim($this->Activities);

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
		if (trim($this->Profile_image) !== '')
		{
			if (strpos($this->Profile_image, '://'))
			{
				return $this->Profile_image;
			}

			return Zend_Controller_Front::getInstance()->getBaseUrl() . '/uploads/' . $this->Profile_image;
		}

		return $default;
	}

	/**
	 * Returns user's geolocation latitude.
	 *
	 * @return	float
	 */
	public function lat()
	{
		$address = $this->findDependentRowset('Application_Model_Address')->current();

		if ($address)
		{
			return $address->latitude;
		}

		return My_Ip::geolocation()[0];
	}

	/**
	 * Returns user's geolocation longitude.
	 *
	 * @return	float
	 */
	public function lng()
	{
		$address = $this->findDependentRowset('Application_Model_Address')->current();

		if ($address)
		{
			return $address->longitude;
		}

		return My_Ip::geolocation()[1];
	}

	/**
	 * Returns user's address.
	 *
	 * @return	string
	 */
	public function address()
	{
		$address = $this->findDependentRowset('Application_Model_Address')->current();

		if ($address)
		{
			return $address->address;
		}

		// TODO: remove
		
		if (!My_Ip::geolocation(false))
		{
			return Zend_Registry::get('config_global')->geolocation->address;
		}

		return '';
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
		'Application_Model_Address',
		'Application_Model_Message',
		'Application_Model_Friends',
		'Application_Model_UserProfile'
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
		'Receiver' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Message',
			'refColumns' => 'receiver_id'
		),
		'Sender' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Message',
			'refColumns' => 'sender_id'
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
			$db->select()
				->setIntegrityCheck(false)
				->from($db, '*')
				->joinLeft(
					'user_profile',
					'user_data.id = user_profile.user_id',
					array(
						'user_profile.public_profile',
						'user_profile.Activities',
						'user_profile.Looking_for',
						'user_profile.Gender'
					)
				)
				->joinLeft(
					'address',
					'user_data.id = address.user_id',
					array(
						'address.address',
						'address.latitude',
						'address.longitude'
					)
				)
				->where('user_data.id =?', $id)
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
			$db->select()
				->setIntegrityCheck(false)
				->from($db, '*')
				->joinLeft(
					'user_profile',
					'user_data.id = user_profile.user_id',
					array(
						'user_profile.public_profile',
						'user_profile.Activities',
						'user_profile.Looking_for',
						'user_profile.Gender'
					)
				)
				->joinLeft(
					'address',
					'user_data.id = address.user_id',
					array(
						'address.address',
						'address.latitude',
						'address.longitude'
					)
				)
				->where('Email_id =?', $email)
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

		$inviteStausModel = new Application_Model_Invitestatus;
		$inviteStausModel->insert(array(
			'user_id' => $user->id,
			'invite_count' => 10,
			'created' => new Zend_Db_Expr('NOW()'),
			'updated' => new Zend_Db_Expr('NOW()')
		));

		$addressModel = new Application_Model_Address;
		$addressModel->insert(array(
			'user_id' => $user->id,
			'address' => $data["address"],
			'latitude' => $data["latitude"],
			'longitude' => $data["longitude"]
		));

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
				$address = (new Application_Model_Address)->createRow(array('user_id' => $user->id));
			}

			$address->address = $data['address'];
			$address->latitude = $data['latitude'];
			$address->longitude = $data['longitude'];
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
