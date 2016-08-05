<?php
/**
 * Friends model class.
 */
class Application_Model_Friends extends Zend_Db_Table_Abstract
{
	/**
	 * Friend status.
	 * @var array
	 */
	public $status = [
		'awaiting' => 0,
		'confirmed' => 1,
		'rejected' => 2,
	];

	/**
	 * Table name.
	 * @var string
	 */
	protected $_name = 'friends';

	/**
	 * Associative array map of declarative referential integrity rules.
	 * @var array
	 */
	protected $_referenceMap = [
		'FriendReceiver' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'receiver_id'
		],
		'FriendSender' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'sender_id'
		]
	];

	/**
	 * Checks if users are friends.
	 *
	 * @param	integer	$user1
	 * @param	integer	$user2
	 *
	 * @reutrn	mixed Zend_Db_Table_Row on success, otherwise NULL
	 */
	public function isFriend($user1, $user2)
	{
		return $this->fetchRow(
			$this->select()
				->where('status=' . $this->status['confirmed'])
				->where('((sender_id=' . $user1->id . ' AND receiver_id=' . $user2->id . ')')
				->orWhere('(sender_id=' . $user2->id . ' AND receiver_id=' . $user1->id . '))')
		);
	}

	/**
	 * Find records by user ID.
	 *
	 * @param mixed $user
	 * @param array $options
	 * @return array
	 */
	public function findAllByUserId($user, array $options=[])
	{
		$query = $this->select()->setIntegrityCheck(false)
			->from(['f' => 'friends'])
			->where('f.receiver_id=' . $user['id'] . ' OR f.sender_id=' . $user['id'])
			->where('f.status=' . $this->status['confirmed'])
			->joinLeft(['ur' => 'user_data'], 'ur.id=f.receiver_id', [
				'receiver_name' => 'Name',
				'receiver_email' => 'Email_id',
				'receiver_image_id' => 'image_id',
				'receiver_image_name' => 'image_name',
				'receiver_birthday' => 'Birth_date',
				'receiver_gender' => 'gender',
				'receiver_activity' => 'activity'
			])
			->joinLeft(['us' => 'user_data'], 'us.id=f.receiver_id', [
				'sender_name' => 'Name',
				'sender_email' => 'Email_id',
				'sender_image_id' => 'image_id',
				'sender_image_name' => 'image_name',
				'sender_birthday' => 'Birth_date',
				'sender_gender' => 'gender',
				'sender_activity' => 'activity'
			])
			->limit($options['limit'],
				My_ArrayHelper::getProp($options, 'offset', 0));

		if (!empty($options['address']))
		{
			$query->joinLeft(['ar' => 'address'], 'ar.id=ur.address_id', [
				'receiver_address' => 'address',
				'receiver_latitude' => 'latitude',
				'receiver_longitude' => 'longitude',
				'receiver_street_name' => 'street_name',
				'receiver_street_number' => 'street_number',
				'receiver_city' => 'city',
				'receiver_state' => 'state',
				'receiver_country' => 'country',
				'receiver_zip' => 'zip',
				'receiver_timezone' => 'timezone'
			]);
			$query->joinLeft(['as' => 'address'], 'as.id=us.address_id', [
				'sender_address' => 'address',
				'sender_latitude' => 'latitude',
				'sender_longitude' => 'longitude',
				'sender_street_name' => 'street_name',
				'sender_street_number' => 'street_number',
				'sender_city' => 'city',
				'sender_state' => 'state',
				'sender_country' => 'country',
				'sender_zip' => 'zip',
				'sender_timezone' => 'timezone'
			]);
		}

		return $this->fetchAll($query);
	}

	/**
	 * Returns friends count by user ID.
	 *
	 * @param	integer	$user_id
	 *
	 * @return	integer
	 */
	public function getCountByUserId($user_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, array('count(*) as result_count'))
				->where('(receiver_id=' . $user_id . ' OR sender_id=' . $user_id . ')')
				->where('status=?', 1)
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}

	/**
	 * Returns friends count by receiver ID.
	 *
	 * @param	integer	$receiver_id
	 * @return	integer
	 */
	public function getCountByReceiverId($receiver_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, array('count(*) as result_count'))
				->where('receiver_id=?', $receiver_id)
				->where('status=1')
				->where('notify=0')
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}

	/**
	 * Finds record by ID.
	 *
	 * @param	integer $id
	 * return	mixed If success Zend_Db_Table_Row, otherwise NULL
	 */
	public function findById($id)
	{
		$model = new self;
		$result = $model->fetchRow(
			$model->select()->where('id=?', $id)
		);
		return $result;
	}
}
