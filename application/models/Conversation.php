<?php
/**
 * This is the model class for table "conversation".
 */
class Application_Model_Conversation extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	protected $_name = 'conversation';

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'To' => array(
			'columns' => 'to_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		),
		'From' => array(
			'columns' => 'from_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		),
	);

	/**
	 * Saves form.
	 *
	 * @param	array	$data
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function save(array $data)
	{
		$conversation = $this->createRow($data);
		$conversation->created_at = new Zend_Db_Expr('NOW()');
		$conversation->status = new Zend_Db_Expr('0');
		$conversation->save();

		return $conversation;
	}

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
