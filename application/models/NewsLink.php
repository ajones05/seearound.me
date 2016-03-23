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
    protected $_dependentTables = [
		'Application_Model_News',
		'Application_Model_Image'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'News' => [
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		],
		'Image' => [
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		]
	];
}
