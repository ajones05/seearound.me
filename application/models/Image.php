<?php
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
	 * @param	array $thumbsDimensions
	 * @return Zend_Db_Table_Row_Abstract
	 */
	public function save($path, $name, &$thumbs, array $thumbsDimensions=[])
	{
		$image = ['path' => $path . '/' . $name];

		list($image['width'], $image['height']) =
			getimagesize(ROOT_PATH_WEB . '/' . $image['path']);

		if ($thumbsDimensions != null)
		{
			$createThumbs = [];

			foreach ($thumbsDimensions as $thumb)
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
			$thumbs = [];

			foreach ($thumbsDimensions as $thumb)
			{
				$path = $thumb[1] . '/' . $name;
				list($width, $height) = getimagesize(ROOT_PATH_WEB . '/' . $path);
				$thumbs[$thumb[0][0].'x'.$thumb[0][1]] = [
					'image_id' => $image['id'],
					'path' => $path,
					'width' => $width,
					'height' => $height,
					'thumb_width' => $thumb[0][0],
					'thumb_height' => $thumb[0][1]
				];
			}

			My_Query::multipleInsert('image_thumb', $thumbs);
		}

		return $image;
	}
}
