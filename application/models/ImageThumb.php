<?php
/**
 * This is the model class for table "image_thumb".
 */
class Application_Model_ImageThumb extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'image_thumb';

	/**
	 * @var	array
	 */
	protected $_dependentTables = [
		'Application_Model_Image'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'Image' => array(
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		),
	];
}
