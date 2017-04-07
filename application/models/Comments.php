<?php
/**
 * This is the model class for table "comments".
 */
class Application_Model_Comments extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'comments';

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'User' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'user_id'
		],
		'News' => [
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		]
	];

	/**
	 * Returns an instance of a Zend_Db_Table_Select object.
	 *
	 * @param array $options
	 * @return Zend_Db_Table_Select
	 */
	public function publicSelect(array $options=[])
	{
		return parent::select()
			->setIntegrityCheck(false)
			->from(['c' => 'comments'], 'c.*')
			->join(['p' => 'news'], 'p.id=c.news_id',
				My_ArrayHelper::getProp($options, 'post', ''))
			->where('c.isdeleted=0 AND p.isdeleted=0');
	}

	/**
	 * Finds records by news ID.
	 *
	 * @param integer $news_id
	 * @param array $options
	 * @return array
	 */
	public function findAllByNewsId($news_id, array $options)
	{
		$query = $this->select()
			->setIntegrityCheck(false)
			->from(['c' => 'comments'])
			->where('c.news_id=?', $news_id)
			->where('c.isdeleted=?', 0)
			->join(['owner' => 'user_data'], 'owner.id=c.user_id',
				['owner_name' => 'Name', 'owner_image_name' => 'image_name'])
			->order('c.id DESC')
			->group('c.id')
			->limit($options['limit'], My_ArrayHelper::getProp($options,'start',0));

		return $this->fetchAll($query);
	}

	/**
	 * Checks if comment id valid.
	 *
	 * @param integer $comment_id
	 * @param mixed $comment
	 * @param array $options
	 * @return boolean
	 */
	public static function checkId($comment_id, &$comment, array $options=[])
	{
		if ($comment_id == null)
		{
			return false;
		}

		$comment = self::findById($comment_id, $options);

		return $comment != null;
	}

	/**
	 * Finds record by ID.
	 *
	 * @param integer $id
	 * @param array $options
	 * return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findById($id, array $options=[])
	{
		$model = new self;
		return $model->fetchRow($model->publicSelect($options)
			->where('c.id=?', $id));
	}

	/**
	 * Checkd if user can edit comment.
	 *
	 * @param mixed $comment
	 * @param mixed $post
	 * @param mixed $user
	 * @return boolean
	 */
	public static function canEdit($comment, $post, $user)
	{
		return $user && ($user['id'] == $comment['user_id'] ||
			$user['id'] == $post['user_id'] ? 1 : 0);
	}

	/**
	 * Renders comment content.
	 *
	 * @param mixed $comment
	 * @param integer $limit
	 * @return string
	 */
	public static function renderContent($comment, $limit=false)
	{
		$output = '';

		for ($i = 0; $i < strlen($comment['comment']);)
		{
			if (preg_match('/^' . My_Regex::url() . '/ui', substr($comment['comment'], $i), $matches))
			{
				$output .= '<a href="' . htmlspecialchars(My_CommonUtils::renderLink($matches[0])) . '" target="_blank">' . $matches[0] . '</a>';
				$i += strlen($matches[0]);
			}
			else
			{
				$output .= preg_replace('/\n/', '<br>', $comment['comment'][$i++]);
			}

			if ($limit && $i > $limit)
			{
				$output = trim($output) . '... <a href="#" class="moreButton">See more...</a>';
				break;
			}
		}

		return $output;
	}

	/**
	 * Returns view more label.
	 *
	 * @param integer $count
	 * @param integer $limit
	 * @return string
	 */
	public static function viewMoreLabel($count, $limit = 30)
	{
		$label = 'Show ';

		if ($count <= $limit)
		{
			$label .= $count . ' more';
		}
		else
		{
			$label .= 'previous';
		}

		$label .= ' comment';

		if ($count != 1)
		{
			$label .= 's';
		}

		return $label;
	}

	/**
	 * Returns row by attributes.
	 *
	 * @param array $attr
	 * @return stdClass|null
	 */
	public function findByAttributes(array $attr)
	{
		$query = $this->select();
		foreach ($attr as $field => $value)
		{
			if ($value === null)
			{
				$query->where($field . ' IS NULL');
			}
			else
			{
				$query->where($field . '=?', $value);
			}
		}
		return $this->fetchRow($query);
	}

	/**
	 * Returns post comment user.
	 *
	 * @param array|Zend_Db_Table_Row_Abstract $user The current user.
	 * @return array|Zend_Db_Table_Row_Abstract
	 */
	public static function getCommentUser($user)
	{
		$settings =  Application_Model_Setting::getInstance();

		if (!empty($settings['comment_randomUserEnable']))
		{
			$forUsers = array_filter(explode(',',
				My_ArrayHelper::getProp($settings, 'comment_randomForUsers')));

			if (in_array($user['id'], $forUsers))
			{
				$fromUsers = array_filter(explode(',',
					My_ArrayHelper::getProp($settings, 'comment_randomFromUsers')));

				if ($fromUsers != null)
				{
					do
					{
						$user_id = $fromUsers[mt_rand(0, count($fromUsers)-1)];
						$randomUser = Application_Model_User::findById($user_id);

						if ($randomUser != null)
						{
							return $randomUser;
						}

						$fromUsers = array_values(array_diff($fromUsers, [$user_id]));
					}
					while (count($fromUsers) > 0);
				}
			}
		}

		return $user;
	}
}
