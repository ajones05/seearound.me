<?php
/**
 * Row class for friends.
 */
class Application_Model_FriendsRow extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Saves row and write status log.
	 *
	 * @param	Application_Model_UserRow $user
	 * @return	Application_Model_FriendsRow
	 */
	public function updateStatus($user)
	{
		parent::save();

		(new Application_Model_FriendLog)->insert([
			'friend_id' => $this->id,
			'user_id' => $user->id,
			'status_id' => $this->status
		]);

		return $this;
	}
}

/**
 * Friends model class.
 */
class Application_Model_Friends extends Zend_Db_Table_Abstract
{
	/**
	 * Friend status.
	 *
	 * @var	array
	 */
	public $status = array(
		'awaiting' => 0,
		'confirmed' => 1,
		'rejected' => 2,
	);

	/**
	 * @var	string
	 */
	protected $_name = 'friends';

    /**
     * Classname for row.
     *
     * @var string
     */
    protected $_rowClass = 'Application_Model_FriendsRow';

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'FriendReceiver' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'reciever_id'
        ),
		'FriendSender' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'sender_id'
        )
    );

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
				->where('((sender_id=' . $user1->id . ' AND reciever_id=' . $user2->id . ')')
				->orWhere('(sender_id=' . $user2->id . ' AND reciever_id=' . $user1->id . '))')
		);
	}

	/**
	 * Find records by user ID.
	 *
	 * @param	integer	$user_id
	 *
	 * @return	array
	 */
	public function findAllByUserId($user_id, $limit, $offset)
	{
		$result = $this->fetchAll(
			$this->select()
				->where('reciever_id=' . $user_id . ' OR sender_id=' . $user_id)
				->where('status=?', 1)
				->limit($limit, $offset)
		);

		return $result;
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
				->where('(reciever_id=' . $user_id . ' OR sender_id=' . $user_id . ')')
				->where('status=?', 1)
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}

	/**
	 * Find records by receiver ID.
	 *
	 * @param	integer	$receiver_id
	 * @param	integer	$status
	 * @param	integer	$limit
	 * @param	integer	$offset
	 *
	 * @return	array
	 */
	public function findAllByReceiverId($receiver_id, $status, $limit = null, $offset = null)
	{
		$result = $this->fetchAll(
			$this->select()
				->where('reciever_id=?', $receiver_id)
				->where('status=?', $status)
				->limit($limit, $offset)
		);

		return $result;
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
				->where('reciever_id=?', $receiver_id)
				->where('status=1')
				->where('notify=0')
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}
}
