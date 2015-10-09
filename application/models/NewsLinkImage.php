<?php
/**
 * This is the model class for table "news_link_image".
 */
class Application_Model_NewsLinkImage extends Zend_Db_Table_Abstract
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'news_link_image';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_NewsLink',
		'Application_Model_Image'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'NewsLink' => array(
			'columns' => 'news_link_id',
			'refTableClass' => 'Application_Model_NewsLink',
			'refColumns' => 'id'
		),
		'Image' => array(
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		)
	);
}
