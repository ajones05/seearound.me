<?php

class Application_Model_NewsRow extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Renders news content.
	 *
	 * @param  mixed	$limit
	 * @return string
	 */
	public function renderContent($limit = false)
	{
		$newsLink = $this->findDependentRowset('Application_Model_NewsLink');
		$output = '';

		for ($length = $i = 0; $i < strlen($this->news);)
		{
			$link_limit = false;

			if (preg_match('/^' . My_CommonUtils::$link_regex . '/', substr($this->news, $i), $matches))
			{
				$i += strlen($matches[0]);

				$output .= '<a href="' . My_CommonUtils::renderLink($matches[0]) . '" target="_blank">';

				if ($limit && $length + strlen($matches[0]) > $limit)
				{
					$output .= My_StringHelper::stringLimit($matches[0], $limit - $length) . '...';
					$link_limit = true;
				}
				else
				{
					$output .= $matches[0];
					$length += strlen($matches[0]);
				}

				$output .= '</a>';
			}
			else
			{
				$output .= preg_replace('/\n/', '<br>', $this->news[$i++]);
				$length++;
			}

			if ($limit && ($link_limit || $length > $limit))
			{
				$output = trim($output);

				if (!$link_limit)
				{
					$output .= '...';
				}

				$output .= ' <a href="#" class="moreButton">More</a>';

				break;
			}
		}

		if (count($newsLink))
		{
			$output .= My_ViewHelper::render('news/link-meta.html', $newsLink->current()->toArray());
		}

		return $output;
	}
}

class Application_Model_News extends Zend_Db_Table_Abstract
{
	/**
	 * @var	Application_Model_News
	 */
	protected static $_instance;

    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'news';

    /**
     * Classname for row.
     *
     * @var string
     */
    protected $_rowClass = 'Application_Model_NewsRow';

	/**
	 * @var	array
	 */
    protected $_dependentTables = array(
		'Application_Model_Comments',
		'Application_Model_NewsLink'
	);

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'User' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'user_id'
        ),
		'Comments' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Comments',
			'refColumns' => 'news_id'
        ),
		'Links' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_NewsLink',
			'refColumns' => 'news_id'
        )
    );

    public static function getInstance() 
    {
            if (null === self::$_instance) {
                    self::$_instance = new self();
            }		
            return self::$_instance;
    }

	/*
     * Returns an instance of a Zend_Db_Table_Select object.
     *
     * @param bool $withFromPart Whether or not to include the from part of the select based on the table
     * @return Zend_Db_Table_Select
     */
    public function publicSelect($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
		return parent::select($withFromPart)->where('news.isdeleted =?', 0);
    }

	/**
	 * Search news by parameters.
	 *
	 * @param	array $parameters
	 * @param	Application_Model_UserRow $user
	 *
	 * @return	array
	 */
	public function search(array $parameters, Application_Model_UserRow $user)
	{
		$query = $this->publicSelect()->setIntegrityCheck(false);

		if (trim(My_ArrayHelper::getProp($parameters, 'keywords')) !== '')
		{
			$query->where('news.news LIKE ?', '%' . $parameters['keywords'] . '%');
		}

		$filter = My_ArrayHelper::getProp($parameters, 'filter');

		if ($filter != null)
		{
			switch ($filter)
			{
				case 'interest':
					$interests = $user->parseInterests();

					if (count($interests))
					{
						$adapter = $this->getAdapter();

						foreach ($interests as &$_interest)
						{
							$_interest = 'news.news LIKE ' . $adapter->quote('%' . $_interest . '%');
						}

						$query->where(implode(' OR ', $interests));
					}

					break;
				case 'myconnection':
					$query->where('news.user_id =?', $user->id);
					break;
				case 'friends':
					$query->where('news.user_id <>?', $user->id);
					$query->joinLeft('friends', 'news.user_id = friends.sender_id OR news.user_id = friends.reciever_id', '');
					$query->where('(friends.sender_id = ' . $user->id . ' OR friends.reciever_id = ' . $user->id . ')');
					$query->where('friends.status =?', 1);
					break;
			}
		}

		if (count(My_ArrayHelper::getProp($parameters, 'exclude_id', array())))
		{
			foreach ($parameters['exclude_id'] as $id)
			{
				$query->where('news.id <>?', $id);
			}
		}

		$result = $this->fetchAll(
			$query
				->from($this, array(
					'news.*',
					'((news.vote+news.comment+1)/((IFNULL(TIMESTAMPDIFF(HOUR, news.created_date, NOW()), 0)+30)^1.1))*10000 as score',
					// https://developers.google.com/maps/articles/phpsqlsearch_v3#findnearsql
					'(3959 * acos(cos(radians(' . $parameters['latitude'] . ')) * cos(radians(news.latitude)) * ' .
						'cos(radians(news.longitude) - ' . 'radians(' . $parameters['longitude'] . ')) + ' .
						'sin(radians(' . $parameters['latitude'] . ')) * sin(radians(news.latitude)))) AS distance_from_source'
				))
				->having('IFNULL(distance_from_source, 0) < ' . $parameters['radius'])
				->order('score DESC')
				->group('news.id')
				->limit($parameters['limit'], My_ArrayHelper::getProp($parameters, 'start', 0))
		);

		return $result;
	}

	/**
	 * Checks if news id is valid.
	 *
	 * @param	integer	$news_id
	 * @param	mixed	$news
	 * @param	mixed	$deleted
	 *
	 * @return	boolean
	 */
    public static function checkId($news_id, &$news, $deleted = null)
    {
		if ($news_id == null)
		{
			return false;
		}

		$news = self::findById($news_id, $deleted);

		return $news != null;
    }

	/**
	 * Finds record by ID.
	 *
	 * @param	integer	$id
	 *
	 * return	mixed	If success Application_Model_NewsRow, otherwise NULL
	 */
	public static function findById($id, $deleted = null)
	{
		$db = self::getInstance();

		$query = $db->select()->where('id =?', $id);
		
		if ($deleted !== null)
		{
			$query->where('isdeleted =?', $deleted);
		}

		$result = $db->fetchRow($query);

		return $result;
	}

    /**
     * Inserts a new row.
     *
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
    public function insert(array $data)
    {
		$data['updated_date'] = date('Y-m-d H:i:s');

		return parent::insert($data);
	}

	/**
	 * Saves form.
	 *
	 * @param	array	$data
	 * @return	Application_Model_NewsRow
	 */
	public function save(array $data, $news = null)
	{
		$this->_db->beginTransaction();

		try
		{
			if ($news == null)
			{
				$news = (new Application_Model_News)->createRow();
			}
			else
			{
				$links = $news->findDependentRowset('Application_Model_NewsLink');

				if (count($links))
				{
					foreach ($links as $link)
					{
						@unlink(ROOT_PATH . '/uploads/' . $link->link);
						$link->delete();
					}
				}
			}

			// TODO: fix field name
			if (isset($data['address']))
			{
				$data['Address'] = $data['address'];
				unset($data['address']);
			}

			$news->setFromArray($data);
			$news->updated_date = new Zend_Db_Expr('NOW()');
			$news->save();

			if ($news->image == null && preg_match_all('/' . My_CommonUtils::$link_regex . '/', $news->news, $matches))
			{
				foreach ($matches[0] as $link)
				{
					$meta = My_CommonUtils::getLinkMeta($link);

					if (!count($meta))
					{
						continue;
					}

					$title = My_ArrayHelper::getProp($meta, 'property.og:title', My_ArrayHelper::getProp($meta, 'title'));

					if (trim($title) === '')
					{
						continue;
					}

					if (My_ArrayHelper::getProp($meta, 'property.og:image') != null)
					{
						try
						{
							$full_path = null;
							$ext = strtolower(My_ArrayHelper::getProp(pathinfo($meta['property']['og:image']), 'extension', 'tmp'));

							if (!in_array($ext, My_CommonUtils::$imagetype_extension))
							{
								if (preg_match('/^(' . implode('|', My_CommonUtils::$imagetype_extension) . ')/', $ext, $matches))
								{
									$ext = $matches[0];
								}
							}

							if ($ext != 'tmp' && !in_array($ext, My_CommonUtils::$imagetype_extension))
							{
								throw new Exception('Incorrect image extension: ' . $ext);
							}

							do
							{
								$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
								$full_path = ROOT_PATH . '/uploads/' . $name;
							}
							while (file_exists($full_path));

							if (!@copy($meta['property']['og:image'], $full_path))
							{
								throw new Exception("Download image error");
							}

							$imageType = exif_imagetype($full_path);

							if (!isset(My_CommonUtils::$imagetype_extension[$imageType]))
							{
								throw new Exception('Incorrect image type: ' . $imageType);
							}

							if (My_CommonUtils::$imagetype_extension[$imageType] != $ext)
							{
								$name = preg_replace('/' . $ext . '$/', My_CommonUtils::$imagetype_extension[$imageType], $name);

								if (!rename($full_path, ROOT_PATH . '/uploads/' . $name))
								{
									throw new Exception('Rename image error: ' . $full_path);
								}
							}

							My_CommonUtils::createThumbs($full_path, array(
								array(448, 320, ROOT_PATH . '/thumb448x320/' . $name, 1)
							), $imageType);

							$meta['property']['og:image'] = $name;

							list($meta['property']['og:image:width'], $meta['property']['og:image:height']) = 
								getimagesize(ROOT_PATH . '/uploads/' . $name);
						}
						catch (Exception $e)
						{
							$meta['property']['og:image'] = null;
							$meta['property']['og:image:width'] = null;
							$meta['property']['og:image:height'] = null;

							if ($full_path != null)
							{
								@unlink($full_path);
							}
						}
					}

					(new Application_Model_NewsLink)->insert(array(
						'news_id' => $news->id,
						'link' => $link,
						'title' => $title,
						'description' => My_ArrayHelper::getProp($meta, 'property.og:description',
							My_ArrayHelper::getProp($meta, 'name.description')),
						'image' => My_ArrayHelper::getProp($meta, 'property.og:image'),
						'image_width' => My_ArrayHelper::getProp($meta, 'property.og:image:width'),
						'image_height' => My_ArrayHelper::getProp($meta, 'property.og:image:height'),
						'author' => My_ArrayHelper::getProp($meta, 'name.author')
					));

					break;
				}
			}

			$this->_db->commit();

			return $news;
		}
		catch (Exception $e)
		{
			$this->_db->rollBack();

			throw $e;
		}
	}

	/**
	 * Returns select news comments expression.
	 *
	 * @return	Zend_Db_Expr
	 */
	public function commentsSubQuery()
	{
		$commentsModel = new Application_Model_Comments;
		$query = $commentsModel->publicSelect()
			->from($commentsModel, array('user_id', 'news_id', 'COUNT(*) as count'))
			->group('news_id');

		return new Zend_Db_Expr('(' . $query . ')');
	}
}
