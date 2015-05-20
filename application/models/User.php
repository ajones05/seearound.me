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
class Application_Model_User extends My_Db_Table_Abstract
{



    protected $_name     = 'user_data';

    protected $_primary  = array('id');

    protected $_rowClass = 'Application_Model_UserRow';

    protected $_dependentTables = array(
		'Application_Model_News',
		'Application_Model_Comments',
		'Application_Model_Address',
		'Application_Model_Message',
		'Application_Model_Friends',
		'Application_Model_UserProfile'
	);

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

    protected static $_instance = null;

    protected $_filters = array(

        'Name'=>array('StripTags', 'StringTrim'),

        'Email_id' => array('StripTags', 'StringTrim'),

        'Password' => array('StripTags', 'StringTrim'),

        'Re-password' => array('StripTags', 'StringTrim'),

        'Location' => array('StripTags', 'StringTrim')

    );



    protected $_validators = array(

        'Name' => array(

            'allowEmpty' => false,

            array('StringLength', 0, 60),

            array('Alnum', true, array('options' => array('allowWhiteSpace' => true))),

            'messages' => array(

                array(

                    Zend_Validate_StringLength::TOO_LONG => 'Name is greater than 60 characters',

                   

                ),

                array(

                    Zend_Validate_Alnum::INVALID => 'Name contains alphanumeric characters.', 

                    Zend_Validate_Alnum::NOT_ALNUM => 'Name contains special characters.'

                )

            )

        ),

        'Email_id' => array(

            'allowEmpty' => false,

            array('EmailAddress'),

            'messages' => array(

                array(

                    Zend_Validate_EmailAddress::INVALID_FORMAT => 'Please enter a valid email address',

                    Zend_Validate_EmailAddress::INVALID => 'Please enter a valid email address',

                    Zend_Validate_EmailAddress::INVALID_HOSTNAME => 'Please enter a valid email address',

                    Zend_Validate_EmailAddress::INVALID_LOCAL_PART => 'Please enter a valid email address',

                    Zend_Validate_EmailAddress::INVALID_MX_RECORD => 'Please enter a valid email address',

                    Zend_Validate_EmailAddress::INVALID_SEGMENT => 'Please enter a valid email address',

                    Zend_Validate_EmailAddress::QUOTED_STRING => 'Please enter a valid email address'

                )

            )

        ),

        'Password' => array(

            'allowEmpty' => false,

            array('StringLength', 3, 20),

            'messages' => array(

                array(

                    Zend_Validate_StringLength::TOO_LONG => 'Password is greater than 20 characters'

                )	

            )

        ),	

        'Re-password' => array(

            'allowEmpty' => false,

            array('StringLength', 3, 20),

            'messages' => array(

                array(

                    Zend_Validate_StringLength::TOO_LONG => 'Password is greater than 20 characters'

                )	

            )

        ),	

        'Location' => array(

            'allowEmpty' => false,

            array('StringLength', 0, 300),

            'messages' => array(

                array(

                    Zend_Validate_StringLength::TOO_LONG => 'Location is greater than 600 characters'

                )	

            )

        ),

    );



     public function validateData($request, &$data, &$errors) 

    {

        $newsFactory = new Application_Model_NewsFactory();

        $userTable = new Application_Model_User();

        $data = array(

            'Name' => $request->getParam('Name'),

            'Email_id' => $request->getParam('Email_id'),

            'Password' => $request->getParam('Password'),

            'Re-password' => $request->getParam('Re-password'),

            'State_id' => $request->getParam('State'),

           // 'Location' => $request->getParam('Location')

            'Location' =>'Noida'
        );
        
       
        if (($validatedErrors = $userTable->validate($data)) && ($validatedErrors !== true)) {

            $errors = $validatedErrors;

        }

		$auth = Zend_Auth::getInstance();

		if (!$auth->getIdentity()) {
			if($errors['Email_id'] == '' && $data['Email_id'] != '') {

				$select = $userTable->select()

					->where('Email_id =?', $data['Email_id']);

				if($row = $userTable->fetchRow($select)) {

					$errors['Email_id'] = 'This email is already registered with seearound.me';

				}

			}

			if($errors['Password'] == '' && $errors['Re-password'] == '') {

				if($data['Password'] !== $data['Re-password']) {

					$errors['Re-password'] = 'Password not match';

				} else {
				   
				   $lowercase = preg_match('@[a-z]@', $data['Password']);
				   $number    = preg_match('@[0-9]@', $data['Password']);
					
				   if(!$lowercase || !$number || strlen($data['Password']) < 6) {
					 $errors['Password'] = 'Password  minimum of 6 characters, with at least one character or number.';
				   }  
			
			
			   } 
			
			}

		}

    }



    public static function getInstance() 

    {

        if (null === self::$_instance) {

            self::$_instance = new self();

        }		

        return self::$_instance;

    }



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

    

    public function getName($id)

    {

        $userTable = new Application_Model_User();

        $select = $userTable->select()

            -> where('id =?',$id);

        $data = $userTable->fetchRow($select);

        return $data->Name;

    }

    

    public function getImage($id)

    {

        $userTable = new Application_Model_User();

        $select = $userTable->select()

            -> where('id =?',$id);

        $data = $userTable->fetchRow($select);

        return $data->Profile_image;

    }

    

    public function getIntrest($id)

    {

        $userTable = new Application_Model_User();

        $select = $userTable->select()->setIntegrityCheck(false)

            ->from('user_data')

            ->join('user_profile','user_profile.user_id = user_data.id',array('Activities'))

            ->where('user_data.id =?',$id);

        $userTableRow = $userTable->fetchRow($select);

        //$userInterest = explode(",",strtoupper($userTableRow->Activities));

        return $userTableRow;   

    }

    

     public function getUserInterest($id)

    {

        $userTable = new Application_Model_User();

        $select = $userTable->select()->setIntegrityCheck(false)

            ->from('user_data')

            ->join('user_profile','user_profile.user_id = user_data.id',array('Activities'))

            ->where('user_data.id =?',$id);

        $userTableRow = $userTable->fetchRow($select);

        //$userInterest = explode(",",strtoupper($userTableRow->Activities));

        return $userTableRow->Activities;   

    }

    

    public function haveEmail($email,$userId)

    {

        $userTable = new Application_Model_User();

        if($userId){

          $select =  $userTable->select() 

            ->where('Email_id =?',$email)

            ->where('id != ?',$userId);

        } else {

          $select =  $userTable->select() 

            ->where('Email_id =?',$email); 

        }

        $userRow = $userTable->fetchRow($select);

        if($userRow) {

            return true;

        } else {

            return false;

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

    public function getUserProfile($userID){
        if($userID) {
            $select = $this->select()->setIntegrityCheck(false)

                ->from($this, array('id', 'Name','Email_id', 'Profile_image','Network_id','Birth_Date'))

                ->joinLeft('address', 'user_data.id = address.user_id', array('address', 'latitude', 'longitude'))
                
                ->joinLeft('user_profile', 'user_data.id = user_profile.user_id', array('Activities', 'Gender'))

                ->where('user_data.id =?', $userID);

            if($row = $this->fetchAll($select)) {

                return $row;

            }
        }   
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
		$db = self::getInstance();

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
		$db = self::getInstance();

		$result = $db->fetchRow(
			$db->select()->where('user_data.Network_id =?', $network_id)
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
		$db = self::getInstance();

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
	public function register($data)
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
}
