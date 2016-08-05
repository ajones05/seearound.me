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
			'refColumns' => 'reciever_id'
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
