<?php
/**
 * Model class table `user_block`.
 */
class Application_Model_UserBlock extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'user_block';

	/**
	 * @var	array
	 */
	protected $_dependentTables = [
		'Application_Model_User'
	];

	/**
	 * @var	array
	 */
	 protected $_referenceMap = [
		'User' => [
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		],
		'BlockUser' => [
			'columns' => 'block_user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		]
	];

	/**
	 * Checks if users are blocked.
	 *
	 * @param	integer	$user_id
	 * @param	integer	$block_user_id
	 * @reutrn	mixed Zend_Db_Table_Row on success, otherwise NULL
	 */
	public function isBlock($user_id, $block_user_id)
	{
		$query = 'user_id=' . $user_id . ' AND block_user_id=' . $block_user_id;
		return $this->fetchRow($this->select()->where($query));
	}
}
