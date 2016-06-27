<?php

class Application_Model_PostSocial extends Zend_Db_Table_Abstract
{
	/**
   * The table name.
   *
   * @var string
   */
	protected $_name = 'post_social';

	/**
	 * @var	array
	 */
  protected $_dependentTables = [
		'Application_Model_News'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'News' => [
			'columns' => 'post_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		]
	];
}
