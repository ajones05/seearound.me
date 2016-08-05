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
			$log = Zend_Controller_Front::getInstance()->getParam('bootstrap')
				->getResource('Log');

			if ($log)
			{
				$log->log('Delete image file ' . $this->path . ' error: ' .
					var_export(error_get_last(), true), Zend_Log::ERR);
			}
		}

		$thumbs = $this->findDependentRowset('Application_Model_ImageThumb');

		foreach ($thumbs as $thumb)
		{
			if (!@unlink(ROOT_PATH_WEB . '/' . $thumb->path))
			{
				if ($log)
				{
					$log->log('Delete thumb file ' . $thumb->path . ' error: ' .
						var_export(error_get_last(), true), Zend_Log::ERR);
				}
			}
		}

		$db = Zend_Db_Table::getDefaultAdapter();
		$db->delete('image_thumb', 'image_id=' . $this->id);

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
	 * @var string
	 */
	protected $_name = 'image';

	/**
	 * Classname for row.
	 * @var string
	 */
	protected $_rowClass = 'Application_Model_ImageRow';

	/**
	 * @var	array
	 */
	protected $_dependentTables = [
		'Application_Model_User',
		'Application_Model_News',
		'Application_Model_NewsLink'
	];

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
	 * Finds record by ID.
	 *
	 * @param integer $id
	 * return mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findById($id)
	{
		$model = new self;
		return $model->fetchRow($model->select()->where('id=?', $id));
	}

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
		$image = ['path' => $path . '/' . $name];

		list($image['width'], $image['height']) =
			getimagesize(ROOT_PATH_WEB . '/' . $image['path']);

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

			$image['id'] = $this->insert($image);

			My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image['path'],
				$createThumbs);

			$thumbModel = new Application_Model_ImageThumb;
			$thumbsData = [];

			foreach ($thumbs as $thumb)
			{
				$path = $thumb[1] . '/' . $name;
				list($width, $height) = getimagesize(ROOT_PATH_WEB . '/' . $path);
				$thumbsData[] = [
					'image_id' => $image['id'],
					'path' => $path,
					'width' => $width,
					'height' => $height,
					'thumb_width' => $thumb[0][0],
					'thumb_height' => $thumb[0][1]
				];
			}

			My_Query::multipleInsert('image_thumb', $thumbsData);
		}

		return $image;
	}
}
