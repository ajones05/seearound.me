<?php
/**
 * Row class for image thumb.
 */
class Application_Model_ImageThumbRow extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Deletes row and image.
	 *
	 * @return	boolean
	 */
	public function deleteThumb()
	{
		if (!@unlink(ROOT_PATH_WEB . '/' . $this->path))
		{
			throw new Exception('Delete thumb file ' . $this->path . ' error');
		}

		return parent::delete();
	}
}

/**
 * This is the model class for table "image_thumb".
 */
class Application_Model_ImageThumb extends Zend_Db_Table_Abstract
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'image_thumb';

    /**
     * Classname for row.
     *
     * @var string
     */
    protected $_rowClass = 'Application_Model_ImageThumbRow';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_Image'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'Image' => array(
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		),
	);

    /**
     * Save thumb.
     *
     * @var Zend_Db_Table_Row_Abstract
     */
	public function save($path, $image, array $thumb)
	{
		$row = $this->createRow();
		$row->image_id = $image->id;
		$row->path = $path;
		list($row->width, $row->height) = getimagesize(ROOT_PATH_WEB . '/' . $path);
		list($row->thumb_width, $row->thumb_height) = $thumb;
		$row->save(true);

		return $row;
	}
}
