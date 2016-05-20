<?php
/**
 * This is the model class for table "conversation_message".
 */
class Application_Model_ConversationMessage extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	protected $_name = 'conversation_message';

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
		'Conversation' => array(
			'columns' => 'conversation_id',
			'refTableClass' => 'Application_Model_Conversation',
			'refColumns' => 'id'
		)
    );

	/**
	 * Saves form.
	 *
	 * @param	array	$data
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function save(array $data)
	{
		$message = $this->createRow($data);
		$message->is_read = new Zend_Db_Expr('0');
		$message->created_at = new Zend_Db_Expr('NOW()');
		$message->status = new Zend_Db_Expr('0');
		$message->save();

		return $message;
	}

	/**
	 * Returns rows count by conversation ID.
	 *
	 * @param	integer $conversation_id
	 * @return	integer
	 */
	public function getReplyCount($conversation_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, array('count(*) as count'))
				->where('is_first<>1')
				->where('conversation_id=?', $conversation_id)
		);

		return $result->count;
	}

	/**
	 * Finds record by ID.
	 *
	 * @param	integer $id
	 * return	mixed If success Application_Model_Row, otherwise NULL
	 */
	public function findById($id)
	{
		$model = new self;
		$result = $model->fetchRow(
			$model->select()
				->where('id=?', $id)
				->where('status=0')
		);
		return $result;
	}
}
