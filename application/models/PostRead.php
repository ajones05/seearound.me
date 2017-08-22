<?php

/**
 * The table post_read` model class.
 */
class Application_Model_PostRead extends Zend_Db_Table_Abstract
{
	/**
   * The table name.
   *
   * @var string
   */
	protected $_name = 'post_read';

	/**
	 * @var	array
	 */
  protected $_dependentTables = [
		'Application_Model_News',
		'Application_Model_User'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'News' => [
			'columns' => 'post_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		],
		'User' => [
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		]
	];
}
