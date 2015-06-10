<?php

class Application_Model_MessageReply extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	protected $_name = "message_reply";

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'Receiver' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'receiver_id'
        ),
		'Sender' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'sender_id'
        )
    );

    function replyWithUserData($data = array(), $all=false, $limit=5, $offset=0) 
    {
        $select = $this->select()->setIntegrityCheck(false) 
                ->from($this)
                ->join('user_data', "message_reply.sender_id = user_data.id", array("name","user_id"=>"id"));
        if($data && is_array($data)) {
            foreach ($data as $key => $value) {
                $select->where($key."=?",$value);
            }
        }
        $select->order('message_reply.created');
        if(!$all) {
            $select->limit($limit, $offset);
        }
        return $this->fetchAll($select);
    }

	/**
	 *
	 * ...
	 *
	 */
	public function findAllByMessageId($message_id, $limit = null, $offset = null)
	{
		return $this->fetchAll(
			$this->select()
				->where("message_id =?", $message_id)
				->order("created DESC")
				->limit($limit, $offset)
		);
	}

	/**
	 *
	 * ...
	 *
	 */
	public function getCountByMessageId($message_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, array('count(*) as result_count'))
				->where("message_id =?", $message_id)
		);

		if ($result)
		{
			return $result->result_count;
		}

		return 0;
	}
}
