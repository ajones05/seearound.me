<?php

class Application_Model_Message extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	protected $_name = "message";

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'ReplyReceiver' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'receiver_id'
		),
		'ReplySender' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'sender_id'
		)
	);

	/**
	 * Returns an instance of a Zend_Db_Table_Select object.
	 *
	 * @param bool $withFromPart Whether or not to include the from part of the select based on the table
	 * @return Zend_Db_Table_Select
	 */
	public function publicSelect($withFromPart = self::SELECT_WITHOUT_FROM_PART)
	{
		return parent::select($withFromPart)->where('is_deleted =?', 'false')->where('is_valid =?', 'true');
	}

	/**
	 * Finds record by ID.
	 *
	 * @param	integer	$id
	 *
	 * return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findById($id)
	{
		return $this->fetchRow($this->publicSelect()->where('id =?', $id));
	}

	/**
	 * Checks if message id is valid.
	 *
	 * @param	integer	$message_id
	 * @param	mixed	$message
	 *
	 * @return	boolean
	 */
	public function checkId($message_id, &$message)
	{
		if ($message_id == null)
		{
			return false;
		}

		$message = self::findById($message_id);

		return $message != null;
	}

	/**
	 * Saves form.
	 *
	 * @param	array	$data
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function save(array $data)
	{
		$message = (new Application_Model_Message)->createRow($data);
		$message->created = new Zend_Db_Expr('NOW()');
		$message->updated = new Zend_Db_Expr('NOW()');
		$message->is_deleted = 'false';
		$message->is_valid = 'true';
		$message->sender_read = 'true';
		$message->reciever_read = 'false';
		$message->save();

		return $message;
	}
}
