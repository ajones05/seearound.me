<?php
/**
 * This is the model class for table "user_image".
 */
class Application_Model_UserImage extends Zend_Db_Table_Abstract
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'user_image';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_User',
		'Application_Model_Image'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'User' => array(
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		),
		'Image' => array(
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		)
	);
}
