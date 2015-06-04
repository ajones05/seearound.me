<?php

class Application_Model_NewsLink extends Zend_Db_Table_Abstract
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'news_link';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_News'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'News' => array(
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		)
	);
}
