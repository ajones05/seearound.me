<?php
/**
 * This is the model class for table "image".
 */
class Application_Model_News extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	public static $imagePath = 'uploads';

	/**
	 * @var array
	 */
	public static $thumbPath = [
		'320x320' => 'tbnewsimages',
		'448x320' => 'thumb448x320',
		'960x960' => 'newsimages'
	];

	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'news';

	/**
	 * @var	array
	 */
    protected $_dependentTables = [
		'Application_Model_Address',
		'Application_Model_Comments',
		'Application_Model_NewsLink',
		'Application_Model_Voting',
		'Application_Model_PostSocial'
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
		],
		'Social' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_PostSocial',
			'refColumns' => 'post_id'
    ]
	];

	/**
	 * Returns an instance of a Zend_Db_Table_Select object.
	 *
	 * @param array $options
	 * @return Zend_Db_Table_Select
	 */
	public function publicSelect(array $options = [])
	{
		$isCount = My_ArrayHelper::getProp($options, 'count', false);
		$addressFields = $isCount ? '' : ['address', 'latitude', 'longitude',
			'street_name', 'street_number', 'city', 'state', 'country', 'zip'];
		$postFields = $isCount ? ['count' => 'COUNT(news.id)'] : ['news.*'];

		$query = parent::select()->setIntegrityCheck(false)
			->from('news', $postFields)
			->join(['a' => 'address'], 'a.id=news.address_id', $addressFields);

		if (!array_key_exists('deleted', $options) || !$options['deleted'])
		{
			$query->where('news.isdeleted=0');
		}

		if (!empty($options['thumbs']))
		{
			My_Query::setThumbsQuery($query, $options['thumbs'], 'news');
		}

		if (!$isCount)
		{
			$query->join(['owner' => 'user_data'], 'owner.id=news.user_id',
				['owner_name' => 'Name', 'owner_image_name' => 'image_name']);
		}

		if (!empty($options['link']))
		{
			$query->joinLeft(['link' => 'news_link'], 'link.news_id=news.id', [
				'link_id' => 'id',
				'link_link' => 'link',
				'link_title' => 'title',
				'link_description' => 'description',
				'link_author' => 'author',
				'link_image_id' => 'image_id',
				'link_image_name' => 'image_name'
			]);

			if (!empty($options['link']['thumbs']))
			{
				My_Query::setThumbsQuery($query, $options['link']['thumbs'], 'link');
			}
		}

		if (!empty($options['user']))
		{
			if (!empty($options['userVote']))
			{
				$query->joinLeft(
					['v' => 'votings'],
					'(v.news_id=news.id AND v.active=1 AND ' .
						'v.user_id=' . $options['user']['id'] . ')',
					['user_vote' => 'vote']
				);
			}

			if (empty($options['userBlock']))
			{
				$query->joinLeft([
					'ub' => 'user_block'],
					'(ub.block_user_id=news.user_id AND ub.user_id=' .
						$options['user']['id'] . ')',
					''
				);
				$query->where('ub.id IS NULL');
			}
		}

		return $query;
	}

	/**
	 * Returns search news query.
	 *
	 * @param	array $parameters
	 * @param	mixed $user
	 * @param	array $options
	 * @return	Zend_Db_Table_Select
	 */
	public function searchQuery(array $parameters, $user, array $options = [])
	{
		// TODO: refactoring
		$query = $this->publicSelect($options+['user'=>$user])
			->where('news.vote>-4');
		$isCount = My_ArrayHelper::getProp($options, 'count', false);

		if (trim(My_ArrayHelper::getProp($parameters, 'keywords')) !== '')
		{
			$query->where('news.news LIKE ?', '%' . $parameters['keywords'] . '%');
		}

		$order = [];

		switch (My_ArrayHelper::getProp($parameters, 'filter'))
		{
			// My posts
			case '0':
				$query->where('news.user_id=?', $user['id']);
				$order[] = $this->postScore() . ' DESC';
				break;
			// My interests
			case '1':
				$interests = !empty($user['interest']) ?
					explode(', ', $user['interest']) : null;

				if ($interests != null)
				{
					$adapter = $this->getAdapter();

					foreach ($interests as &$_interest)
					{
						$_interest = 'news.news LIKE ' . $adapter->quote('%' . $_interest . '%');
					}

					$query->where(implode(' OR ', $interests));
				}
				$order[] = $this->postScore() . ' DESC';
				break;
			// Following
			case '2':
				$query->where('news.user_id<>?', $user['id']);
				$query->joinLeft(array('f1' => 'friends'), 'f1.sender_id=' . $user['id'], '');
				$query->joinLeft(array('f2' => 'friends'), 'f2.receiver_id=' . $user['id'], '');
				$query->where('((f1.status=1');
				$query->where('news.user_id=f1.receiver_id)');
				$query->orWhere('(f2.status=1');
				$query->where('news.user_id=f2.sender_id))');
				$order[] = $this->postScore() . ' DESC';
				break;
			// Most recent
			case '3':
				$order[] = 'created_date DESC';
				break;
			default:
				$order[] = $this->postScore() . ' DESC';
		}

		if (!empty($parameters['exclude_id']))
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
	 * @param	mixed $user
	 * @param	array $options
	 * @return	array
	 */
	public function search(array $parameters, $user, array $options=[])
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
	 * return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findById($id, array $options=[])
	{
		$model = new self;
		$join = My_ArrayHelper::getProp($options, 'join', true);

		if ($join)
		{
			$query = $model->publicSelect($options);
		}
		else
		{
			$query = $model->select();

			if (!array_key_exists('deleted', $options) || !$options['deleted'])
			{
				$query->where('news.isdeleted=0');
			}
		}
		$result = $model->fetchRow($query->where('news.id=?', $id));
		return $result;
	}

	/**
	 * Saves form.
	 *
	 * @param Zend_Form $form
	 * @param array|Zend_Db_Table_Row_Abstract $user
	 * @param array $address
	 * @param array $image
	 * @param array $thumbs
	 * @param array $link
	 * @param array|Zend_Db_Table_Row_Abstract $post
	 * @return array
	 */
	public function save(Zend_Form $form, $user, &$address, &$image=null,
		&$thumbs=null, &$link=null, &$post=null)
	{
		$data = $form->getValues();

		$data['body'] = $this->filterBody($data['body']);
		$saveData = ['news' => $data['body']];

		if (!$form->isIgnore('address'))
		{
			$address = [
				'address' => $data['address'],
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
				'street_name' => $data['street_name'],
				'street_number' => $data['street_number'],
				'city' => $data['city'],
				'state' => $data['state'],
				'country' => $data['country'],
				'zip' => $data['zip'],
				'timezone' => $data['timezone']
			];

			if ($post !== null)
			{
				$this->_db->update('address', $address, 'id=' . $post['address_id']);
			}
			else
			{
				$this->_db->insert('address', $address);
				$saveData['address_id'] = $this->_db->lastInsertId('address');
			}
		}

		$timeNow = (new DateTime)->format(My_Time::SQL);

		if ($post !== null)
		{
			$saveData['updated_date'] = $timeNow;
		}
		else
		{
			$this->_db->insert('address', $address);
			$saveData['user_id'] = $user['id'];
			$saveData['created_date'] = $timeNow;
		}

		$existImage = !empty($post['image_id']);
		$uploadImage = !empty($data['image']);
		$deleteImage = !empty($data['delete_image']);

		if ($existImage && ($uploadImage || $deleteImage))
		{
			foreach (self::$thumbPath as $path)
			{
				@unlink(ROOT_PATH_WEB . '/' . $path . '/' . $post['image_name']);
			}

			$this->_db->delete('image_thumb', 'image_id=' . $post['image_id']);

			@unlink(ROOT_PATH_WEB . '/' . self::$imagePath . '/' .
				$post['image_name']);

			$this->_db->delete('image', 'id=' . $post['image_id']);
		}

		if ($uploadImage)
		{
			$image = (new Application_Model_Image)->save(self::$imagePath, $data['image'], $thumbs, [
				[[320,320], 'tbnewsimages'],
				[[448,320], 'thumb448x320', 2],
				[[960,960], 'newsimages']
			]);

			$saveData['image_id'] = $image['id'];
			$saveData['image_name'] = $data['image'];
		}
		elseif ($existImage)
		{
			if ($deleteImage)
			{
				$saveData['image_id'] = null;
				$saveData['image_name'] = null;
			}
			else
			{
				$image = [
					'id' => $post['image_id'],
					'path' => self::$imagePath . '/' . $post['image_name']
				];
			}
		}

		if ($post !== null)
		{
			$this->update($saveData, 'id=' . $post['id']);
		}
		else
		{
			$post = ['id' => $this->insert($saveData)];
		}

		if (!empty($post['link_id']))
		{
			foreach (Application_Model_NewsLink::$thumbPath as $path)
			{
				@unlink(ROOT_PATH_WEB . '/' . $path . '/' . $post['link_image_name']);
			}

			if (!empty($post['link_image_id']))
			{
				$this->_db->delete('image_thumb', 'image_id=' . $post['link_image_id']);

				@unlink(ROOT_PATH_WEB . '/' . Application_Model_NewsLink::$imagePath .
					'/' . $post['link_image_name']);

				$this->_db->delete('image', 'id=' . $post['link_image_id']);
			}

			$this->_db->delete('news_link', 'id=' . $post['link_id']);
		}

		$link = (new Application_Model_NewsLink)->saveLink([
			'id' => $post['id'],
			'news' => $data['body'],
		]);

		if (!is_array($post))
		{
			$post = $post->toArray();
		}

		return $saveData + $post;
	}

	/**
	 * Checkd if user can edit post.
	 *
	 * @param mixed $post
	 * @param mixed $user
	 * @return boolean
	 */
	public static function canEdit($post, $user)
	{
		return $post['user_id'] == $user['id'] ? 1 : 0;
	}

	/**
	 * Checkd if user can delete post.
	 *
	 * @param mixed $post
	 * @param mixed $user
	 * @return boolean
	 */
	public static function canDelete($post, $user)
	{
		return ($user['is_admin'] || $post['user_id'] == $user['id']) ? 1 : 0;
	}

	/**
	 * Returns most interesting order.
	 *
	 * @return string
	 */
	public function postScore()
	{
		return '((news.vote+news.comment+4)/' .
			'((IFNULL(TIMESTAMPDIFF(HOUR,news.created_date,NOW()),0)+12)^1.4))*10000';
	}

	/**
	 * Returnds post thumbail path.
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
	 * Returnds post image path.
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

	/**
	 * Renders post content.
	 *
	 * @param array|Zend_Db_Table_Row_Abstract $post
	 * @param array $options
	 * @return string
	 */
	public static function renderContent($post, array $options=[])
	{
		$limit = My_ArrayHelper::getProp($options, 'limit');
		$link = empty($post['image_id']) ? My_ArrayHelper::getProp($options, 'link') : null;
		$linksCount = preg_match_all('/' . My_Regex::url() . '/ui', $post['news']);

		$output = '';

		for ($length = $i = 0; $i < strlen($post['news']);)
		{
			$link_limit = false;
			$subString = substr($post['news'], $i);

			if (preg_match('/^' . My_Regex::url() . '/ui', $subString, $matches) ||
				preg_match('/^#(?P<hashtag>\w+)/', $subString, $matches))
			{
				if (!empty($matches['hashtag']) || $linksCount > 1 || $link === null)
				{
					if (!empty($matches['hashtag']))
					{
						$settings =  Application_Model_Setting::getInstance();
						$renderLink = $settings['server_requestScheme'] . '://' .
							$settings['server_httpHost'] . '/?keywords=' . $matches['hashtag'];
					}
					else
					{
						$renderLink = My_CommonUtils::renderLink($matches[0]);
					}

					$output .= '<a href="' . $renderLink . '" target="_blank">';

					if ($limit !== null && $length + strlen($matches[0]) > $limit)
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
				$output .= preg_replace('/\n/', '<br>', $post['news'][$i++]);
				$length++;
			}

			if ($limit !== null && ($link_limit || $length > $limit))
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

		if ($link !== null)
		{
			$output .= My_ViewHelper::render('post/_link', ['link' => $link]);
		}

		return preg_replace('/\s{2,}/', ' ', $output);
	}

	/**
	 * Filters body.
	 *
	 * @param string $body
	 * @return string
	 */
	public static function filterBody($body)
	{
		return preg_replace(
			// remove tracking parameters
			'/([&?]utm_\w+=\w+|#link_time=\d+)/',
			'', $body);
	}
}
