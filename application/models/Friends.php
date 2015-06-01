<?php



class Application_Model_FriendsRow extends Zend_Db_Table_Row_Abstract 
{

    

}



class Application_Model_Friends extends Zend_Db_Table_Abstract {

    protected $_name     = 'friends';

    protected $_primary  = 'id';

    protected $_rowClass = 'Application_Model_FriendsRow';

    protected static $_instance = null;

    protected $_dependentTables = array();

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

    public static function getInstance() {

        if (null === self::$_instance) {

            self::$_instance = new self();

        }

        return self::$_instance;

    }    

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



    public function getdata($data=array(), $all=false, $limit=null, $offset=null, $order=array(), $group=null) {

        $select = $this->select();

        if(count($data) > 0){

            foreach($data as $index => $value) {

                $select->where($index." =?", $value);

            }

        }

        

        if($order) {

            $select->order($order['field']." ".$order['type']);

        }

        

        if($group) {

            $select->group($group);

        }

        

        if($limit) {

            if($offset) {

                $select->limit($limit, $offset);

            }else {

                $select->limit($limit);

            }

        }

        

        if($all) {

            return $this->fetchAll($select);

        }else {

            return $this->fetchRow($select);

        }

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
	 * @param	integer	$status
	 *
	 * @return	integer
	 */
	public function getCountByReceiverId($receiver_id, $status)
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, array('count(*) as result_count'))
				->where('reciever_id=?', $receiver_id)
				->where('status=?', $status)
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}
}
