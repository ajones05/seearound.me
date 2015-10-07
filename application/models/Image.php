<?php
/**
 * Row class for image.
 */
class Application_Model_ImageRow extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Finds image thumb by dimensions.
	 *
	 * @param	array $thumb
	 * @return	mixed Zend_Db_Table_Row_Abstract on success, otherwise NULL
	 */
	public function findThumb(array $thumb)
	{
		return (new Application_Model_ImageThumb)->fetchAll(array(
			'image_id=' . $this->id,
			'thumb_width=' . $thumb[0],
			'thumb_height=' . $thumb[1]
		));
	}
}

/**
 * This is the model class for table "image".
 */
class Application_Model_Image extends Zend_Db_Table_Abstract
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'image';

    /**
     * Classname for row.
     *
     * @var string
     */
    protected $_rowClass = 'Application_Model_ImageRow';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_NewsImage',
		'Application_Model_NewsLinkImage'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'NewsImage' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_NewsImage',
			'refColumns' => 'image_id'
		),
		'NewsLinkImage' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_NewsLinkImage',
			'refColumns' => 'image_id'
		)
	);

    /**
     * Save image.
     *
     * @var Zend_Db_Table_Row_Abstract
     */
	public function save($path)
	{
		$row = $this->createRow();
		$row->path = $path;
		list($row->width, $row->height) = getimagesize(ROOT_PATH . $path);
		$row->save(true);

		return $row;
	}
}
