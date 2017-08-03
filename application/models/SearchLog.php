<?php

class Application_Model_SearchLog extends Zend_Db_Table_Abstract
{
	/**
   * The table name.
   *
   * @var string
   */
	protected $_name = 'search_log';

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
		]
	];

	/**
	 * Saves the row.
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function save($data)
	{
		$this->update(['is_duplicate' => 1], [
			'user_id=' . $this->_db->quote($data['user_id']),
			'keywords=' . $this->_db->quote($data['keywords'])
		]);

		$data['created_at'] = new Zend_Db_Expr('NOW()');
		$this->insert($data);

		return true;
	}
}
