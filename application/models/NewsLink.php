<?php

class Application_Model_NewsLink extends Zend_Db_Table_Abstract
{
	/**
	 * @var string
	 */
	public static $imagePath = 'uploads';

	/**
	 * @var array
	 */
	public static $thumbPath = [
		'448x320' => 'thumb448x320'
	];

    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'news_link';

	/**
	 * @var	array
	 */
    protected $_dependentTables = [
		'Application_Model_News',
		'Application_Model_Image'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'News' => [
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		],
		'Image' => [
			'columns' => 'image_id',
			'refTableClass' => 'Application_Model_Image',
			'refColumns' => 'id'
		]
	];

	/**
	 * Removes additional parameters from the link.
	 *
	 * @param	string $link
	 * @return	string
	 */
	public function trimLink($link)
	{
		return preg_replace(['/^https?:\/\//','/^www\./',
			'/[?&](utm_source|utm_medium|utm_term|utm_content|utm_campaign)=([^&])+/'],'',$link);
	}

	/**
	 * Finds row by trimed link.
	 *
	 * @param	string	$link
	 * @return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByLinkTrim($link)
	{
		$result = $this->fetchRow(
			$this->select()
				->setIntegrityCheck(false)
				->from(['l' => 'news_link'], 'l.*')
				->where('l.link_trim=?', $link)
				->join(['p' => 'news'], 'p.id=l.news_id', '')
				->where('p.isdeleted=0')
		);
		return $result;
	}

	/**
	 * Returnds link thumbail path.
	 *
	 * @param mixed $row
	 * @param string $thumb Thumbnail dimensions WIDTHxHEIGHT
	 * @param array $options
	 * @return mixed String on success, otherwise NULL
	 */
	public static function getThumb($row, $thumb, array $options=[])
	{
		$imageField = My_ArrayHelper::getProp($options, 'alias') . 'image_name';

		if (empty($row[$imageField]))
		{
			return null;
		}

		return self::$thumbPath[$thumb] . '/' . $row[$imageField];
	}

	/**
	 * Returnds link image path.
	 *
	 * @param mixed $row
	 * @param array $options
	 * @return mixed String on success, otherwise NULL
	 */
	public static function getImage($row, array $options=[])
	{
		$imageField = My_ArrayHelper::getProp($options, 'alias') . 'image_name';

		if (empty($row[$imageField]))
		{
			return null;
		}

		return self::$imagePath . '/' . $row[$imageField];
	}
}
