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
}
