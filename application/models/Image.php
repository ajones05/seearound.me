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
				$log->log('Delete image file ' . $this->path . ' error: ' .
					var_export(error_get_last(), true), Zend_Log::ERR);
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
		'Application_Model_News',
		'Application_Model_NewsLink'
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
		'News' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'image_id'
		),
		'NewsLink' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_NewsLink',
			'refColumns' => 'image_id'
		)
	);

	/**
	 * Save image.
	 *
	 * @param	string $path
	 * @param	string $name
	 * @param	array $thumbs
	 * @return Zend_Db_Table_Row_Abstract
	 */
	public function save($path, $name, array $thumbs=[])
	{
		$image = $this->createRow(['path' => $path . '/' . $name]);
		list($image->width, $image->height) =
			getimagesize(ROOT_PATH_WEB . '/' . $image->path);
		$image->save(true);

		if ($thumbs != null)
		{
			$createThumbs = [];

			foreach ($thumbs as $thumb)
			{
				$createThumbs[] = [
						$thumb[0][0], $thumb[0][1],
						ROOT_PATH_WEB . '/' . $thumb[1] . '/' . $name,
						My_ArrayHelper::getProp($thumb, 2)
				];
			}

			My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path,
				$createThumbs);

			$thumbModel = new Application_Model_ImageThumb;

			foreach ($thumbs as $thumb)
			{
				$thumbModel->save($thumb[1] . '/' . $name, $image, $thumb[0]);
			}
		}

		return $image;
	}
}
