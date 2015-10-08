<?php
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
     * Save thumb.
     *
     * @var Zend_Db_Table_Row_Abstract
     */
	public function save($path, $image, array $thumb)
	{
		$row = $this->createRow();
		$row->image_id = $image->id;
		$row->path = $path;
		list($row->width, $row->height) = getimagesize(ROOT_PATH . '/' . $path);
		list($row->thumb_width, $row->thumb_height) = $thumb;
		$row->save(true);

		return $row;
	}
}
