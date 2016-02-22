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
		return (new Application_Model_ImageThumb)->fetchRow(array(
			'image_id=' . $this->id,
			'thumb_width=' . $thumb[0],
			'thumb_height=' . $thumb[1]
		));
	}

	/**
	 * Deletes row, image and thumbnails.
	 *
	 * @return	boolean
	 */
	public function deleteImage()
	{
		if (!@unlink(ROOT_PATH_WEB . '/' . $this->path))
		{
			$log = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Log');

			if ($log)
			{
				$log->log('Delete image file ' . $this->path . ' error: ' . error_get_last(), Zend_Log::ERR);
			}
		}

		$thumbs = $this->findDependentRowset('Application_Model_ImageThumb');

		foreach ($thumbs as $thumb)
		{
			$thumb->deleteThumb();
		}

		return parent::delete();
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
		'Application_Model_User',
		'Application_Model_NewsImage',
		'Application_Model_NewsLinkImage'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'Thumbs' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_ImageThumb',
			'refColumns' => 'image_id'
		),
		'User' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'image_id'
		),
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
		list($row->width, $row->height) = getimagesize(ROOT_PATH_WEB . '/' . $path);
		$row->save(true);

		return $row;
	}
}
