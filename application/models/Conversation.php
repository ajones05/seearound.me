<?php
/**
 * This is the model class for table "conversation".
 */
class Application_Model_Conversation extends Zend_Db_Table_Abstract
{
	/**
	 * the table name.
	 * @var	string
	 */
	protected $_name = 'conversation';

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'To' => [
			'columns' => 'to_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		],
		'From' => [
			'columns' => 'from_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		],
	];

	/**
	 * Finds record by ID.
	 *
	 * @param	integer	$id
	 * return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findById($id)
	{
		return $this->fetchRow($this->select()->where('id=?', $id));
	}

	/**
	 * Checks if conversation id is valid.
	 *
	 * @param	integer $conversation_id
	 * @param	mixed $conversation
	 * @return	boolean
	 */
	public function checkId($conversation_id, &$conversation)
	{
		if ($conversation_id == null)
		{
			return false;
		}

		$conversation = self::findById($conversation_id);

		return $conversation != null;
	}
}
