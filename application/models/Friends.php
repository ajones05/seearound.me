<?php
/**
 * Friends model class.
 */
class Application_Model_Friends extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	protected $_name = 'friends';

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

    public function setData($data=array(), $check=array()) {

        $select = $this->select();

        $row = array();

        if(count($check) > 0) {

            foreach ($check as $index => $value) {

                $select->where($index." =?", $value);

            }

            if($row = $this->fetchRow($select)) {

                $row->setFromArray($data);

                $row->save();

            }else {

               $row = $this->createRow($data);

               $row->save();

            }

        }else {

            if(count($data)) {

                $row = $this->createRow($data);

                $row->save();

            }

        }

        return $row;

    }

    public function invite($data)

    {

        $select = $this->select()

            ->where("sender_id = ".$data['sender_id']." AND reciever_id = ".$data['reciever_id'])

            ->orWhere("sender_id = ".$data['reciever_id']." AND reciever_id = ".$data['sender_id']); //echo $select; exit;

        $row = $this->fetchAll($select);

        if(count($row) > 0) {

            return $row->toArray(); 

        }else {

            return $this->setData($data)->toArray();

        }

        

    }

    public function getStatus($cuser, $rueser) 

    {

        $select = $this->select()

                ->where("sender_id =".$cuser." AND reciever_id =".$rueser)

                ->orWhere("sender_id =".$rueser." AND reciever_id =".$cuser); //echo $select; exit;

        return $this->fetchRow($select);

        

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
				->where('reciever_id=' . $user_id . ' OR sender_id=' . $user_id)
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
				->where('status=0')
				->where('notify=0')
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}
}
