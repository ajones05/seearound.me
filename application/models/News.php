<?php
use Embed\Embed;

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
		$linksCount = preg_match_all('/' . My_CommonUtils::$link_regex . '/', $this->news);

		$output = '';

		for ($length = $i = 0; $i < strlen($this->news);)
		{
			$link_limit = false;

			if (preg_match('/^' . My_CommonUtils::$link_regex . '/', substr($this->news, $i), $matches))
			{
				if ($linksCount > 1 || !$this->link_id)
				{
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

				$i += strlen($matches[0]);
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

		if ($this->link_id)
		{
			$output .= My_ViewHelper::render('post/_link', ['post' => $this]);
		}

		return preg_replace('/\s{2,}/', ' ', $output);
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
    protected $_dependentTables = [
		'Application_Model_Address',
		'Application_Model_Comments',
		'Application_Model_NewsLink',
		'Application_Model_Voting'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'User' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'user_id'
        ],
		'Address' => [
			'columns' => 'address_id',
			'refTableClass' => 'Application_Model_Address',
			'refColumns' => 'id'
        ],
		'Comments' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Comments',
			'refColumns' => 'news_id'
        ],
		'Links' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_NewsLink',
			'refColumns' => 'news_id'
        ],
		'Voting' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Voting',
			'refColumns' => 'news_id'
        ]
    ];

	/*
     * Returns an instance of a Zend_Db_Table_Select object.
	 *
	 * @param	array $options
     * @return Zend_Db_Table_Select
     */
    public function publicSelect(array $options = [])
    {
		$isCount = My_ArrayHelper::getProp($options, 'count', false);
		$addressFields = $isCount ? '' : ['address', 'latitude', 'longitude',
			'street_name', 'street_number', 'city', 'state', 'country', 'zip'];
		$postFields = $isCount ? ['count' => 'COUNT(news.id)'] : 'news.*';

		$query = parent::select()->setIntegrityCheck(false)
			->from('news', $postFields)
			->where('news.isdeleted=0')
			->join(['a' => 'address'], 'a.id=news.address_id', $addressFields);

		My_Query::setThumbsQuery($query, [[448, 320], [960, 960]], 'news');

		if (!$isCount)
		{
			$query->join(['owner' => 'user_data'], 'owner.id=news.user_id', ['owner_name' => 'Name']);
			My_Query::setThumbsQuery($query, [[26, 26],[55, 55],[320, 320]], 'owner');
		}

		if (!empty($options['link']))
		{
			$query->joinLeft(['link' => 'news_link'], 'link.news_id=news.id', ['link_id' => 'id',
				'link_link' => 'link', 'link_title' => 'title', 'link_description' => 'description',
				'link_author' => 'author', 'link_image_id' => 'image_id']);
			My_Query::setThumbsQuery($query, [[448, 320]], 'link');
		}

		return $query;
    }

	/**
	 * Returns search news query.
	 *
	 * @param	array $parameters
	 * @param	Application_Model_UserRow $user
	 * @param	array $options
	 * @return	Zend_Db_Table_Select
	 */
	public function searchQuery(array $parameters, Application_Model_UserRow $user, array $options = [])
	{
		$query = $this->publicSelect($options);
		$isCount = My_ArrayHelper::getProp($options, 'count', false);

		if (trim(My_ArrayHelper::getProp($parameters, 'keywords')) !== '')
		{
			$query->where('news.news LIKE ?', '%' . $parameters['keywords'] . '%');
		}

		$order = [];
		$filter = My_ArrayHelper::getProp($parameters, 'filter');

		if ($filter != null)
		{
			switch ($filter)
			{
				case '0':
					$query->where('news.user_id=?', $user->id);
					$order[] = $this->mostInterestingOrder();
					break;
				case '1':
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
					$order[] = $this->mostInterestingOrder();
					break;
				case '2':
					$query->where('news.user_id<>?', $user->id);
					$query->joinLeft(array('f1' => 'friends'), 'f1.sender_id=' . $user->id, '');
					$query->joinLeft(array('f2' => 'friends'), 'f2.reciever_id=' . $user->id, '');
					$query->where('((f1.status=1');
					$query->where('news.user_id=f1.reciever_id)');
					$query->orWhere('(f2.status=1');
					$query->where('news.user_id=f2.sender_id))');
					$order[] = $this->mostInterestingOrder();
					break;
				case '3':
					$order[] = 'created_date DESC';
					break;
			}
		}
		else
		{
			$order[] = $this->mostInterestingOrder();
		}

		if (count(My_ArrayHelper::getProp($parameters, 'exclude_id', array())))
		{
			foreach ($parameters['exclude_id'] as $id)
			{
				$query->where('news.id <>?', $id);
			}
		}

		if (!$isCount)
		{
			$query->group('news.id');
		}

		$order[] = 'news.id ASC';

		$query
			->where('IFNULL((3959*acos(cos(radians(' . $parameters['latitude'] . '))*cos(radians(a.latitude))*' .
				'cos(radians(a.longitude)-' . 'radians(' . $parameters['longitude'] . '))+' .
				'sin(radians(' . $parameters['latitude'] . '))*sin(radians(a.latitude)))),0)<' . $parameters['radius'])
			->order($order);

		return $query;
	}

 	/**
 	 * Search news by parameters.
 	 *
 	 * @param	array $parameters
 	 * @param	Application_Model_UserRow $user
 	 * @param	array $options
 	 * @return	array
 	 */
 	public function search(array $parameters, Application_Model_UserRow $user, array $options=[])
 	{
		$query = $this->searchQuery($parameters, $user, $options);
		$result = $this->fetchAll($query->limit($parameters['limit'],
			My_ArrayHelper::getProp($parameters, 'start', 0)));

		return $result;
	}

	/**
	 * Checks if news id is valid.
	 *
	 * @param	integer	$news_id
	 * @param	mixed $news
	 * @param	array $options
	 * @return	boolean
	 */
    public static function checkId($news_id, &$news, array $options=[])
    {
		if ($news_id == null)
		{
			return false;
		}

		$news = self::findById($news_id, $options);

		return $news != null;
    }

	/**
	 * Finds record by ID.
	 *
	 * @param	integer	$id
	 * @param	array $options
	 * return	mixed If success Application_Model_NewsRow, otherwise NULL
	 */
	public static function findById($id, array $options=[])
	{
		$model = new self;
		$join = My_ArrayHelper::getProp($options, 'join', true);
		$query = $join ? $model->publicSelect($options) :
			$model->select()->where('isdeleted=0');
		$result = $model->fetchRow($query->where('news.id=?', $id));
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
		$data['updated_date'] = new Zend_Db_Expr('NOW()');

		return parent::insert($data);
	}

	/**
	 * Saves form.
	 *
	 * @param	array $postData
	 * @param	Application_Model_NewsRow $post
	 * @return	Application_Model_NewsRow
	 */
	public function save(array $data, $post = null)
	{
		$this->_db->beginTransaction();

		try
		{
			$imageModel = new Application_Model_Image;

			if ($post == null)
			{
				$post = $this->createRow($data+[
					'created_date' => new Zend_Db_Expr('NOW()')
				]);
			}
			else
			{
				$link = $post->findParentRow('Application_Model_NewsLink');

				if ($link)
				{
					if ($link->image_id)
					{
						$link->findParentRow('Application_Model_Image')->deleteImage();
					}

					$link->delete();
				}

				$post->setFromArray($data+[
					'updated_date' => new Zend_Db_Expr('NOW()')
				]);
			}

			if ($post->image_id != null && !empty($data['delete_image']))
			{
				$post->findDependentRowset('Application_Model_Image')
					->current()->deleteImage();
				$post->image_id = null;
			}
			elseif (!empty($data['image']))
			{
				$image = $imageModel->save('uploads/' . $data['image']);
				$post->image_id = $image->id;

				$thumb320x320 = 'tbnewsimages/' . $data['image'];
				$thumb448x320 = 'thumb448x320/' . $data['image'];
				$thumb960x960 = 'newsimages/' . $data['image'];

				My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path, array(
					array(320, 320, ROOT_PATH_WEB . '/' . $thumb320x320),
					array(448, 320, ROOT_PATH_WEB . '/' . $thumb448x320, 2),
					array(960, 960, ROOT_PATH_WEB . '/' . $thumb960x960)
				));

				$thumbModel = new Application_Model_ImageThumb;
				$thumbModel->save($thumb320x320, $image, array(320, 320));
				$thumbModel->save($thumb448x320, $image, array(448, 320));
				$thumbModel->save($thumb960x960, $image, array(960, 960));
			}

			$post->save();

			if (empty($data['image']) && preg_match_all('/' . My_CommonUtils::$link_regex . '/', $post->news, $matches))
			{
				foreach ($matches[0] as $link)
				{
					try
					{
						$info = Embed::create($link);
					}
					catch (Exception $e)
					{
						continue;
					}

					$html = $info->getProvider('html');

					$linkModel = new Application_Model_NewsLink;
					$newsLink = $linkModel->createRow([
						'news_id' => $post->id,
						'link' => $link,
						'link_trim' => $linkModel->trimLink($link),
						'title' => $info->getTitle(),
						'description' => $info->getDescription(),
						'author' => $html->bag->get('author')
					]);

					$opengraph = $info->getProvider('opengraph');
					$images = $opengraph->bag->get('images');

					if ($images)
					{
						$image = $images[0];
						$parseImageUrl = parse_url($image);

						if (empty($parseImageUrl['host']))
						{
							$parseUrl = parse_url($link);
							$image = My_ArrayHelper::getProp($parseUrl, 'scheme', 'http') . '://' .
								My_ArrayHelper::getProp($parseUrl, 'host') . '/' . trim($image, '/');
						}

						try
						{
							$full_path = null;
							$ext = strtolower(My_ArrayHelper::getProp(pathinfo($image), 'extension', 'tmp'));

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
								$full_path = ROOT_PATH_WEB . '/uploads/' . $name;
							}
							while (file_exists($full_path));

							if (!@copy($image, $full_path))
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

								if (!rename($full_path, ROOT_PATH_WEB . '/uploads/' . $name))
								{
									throw new Exception('Rename image error: ' . $full_path);
								}
							}

							$image = $imageModel->save('uploads/' . $name);
							$thumb448x320 = 'thumb448x320/' . $name;

							My_CommonUtils::createThumbs(ROOT_PATH_WEB . '/' . $image->path, array(
								array(448, 320, ROOT_PATH_WEB . '/' . $thumb448x320, 2)
							), $imageType);

							(new Application_Model_ImageThumb)
								->save($thumb448x320, $image, array(448, 320));

							$newsLink->image_id = $image->id;
						}
						catch (Exception $e)
						{
							if ($full_path != null)
							{
								@unlink($full_path);
							}
						}
					}

					$newsLink->save(true);
					break;
				}
			}

			$this->_db->commit();

			return $post;
		}
		catch (Exception $e)
		{
			$this->_db->rollBack();

			throw $e;
		}
	}

	/**
	 * Returns most interesting order.
	 *
	 */
	protected function mostInterestingOrder($order='DESC')
	{
		return '((news.vote+news.comment+1)/' .
			'((IFNULL(TIMESTAMPDIFF(HOUR,news.created_date,NOW()),0)+1)^1.4))*10000 ' . $order;
	}
}
