<?php

class Application_Model_NewsRow extends My_Db_Table_Row_Abstract
{
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
		'Application_Model_Comments'
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
        )
    );    

    public static function getInstance() 
    {
            if (null === self::$_instance) {
                    self::$_instance = new self();
            }		
            return self::$_instance;
    }

	/**
	 * Finds news by location.
	 *
	 * @param	float	$lat
	 * @param	float	$lng
	 * @param	float	$radius
	 * @param	integer	$limit
	 * @param	integer	$limitstart
	 * @param	Zend_Db_Table_Select	$attributes
	 *
	 * @return	array
	 */
	public function findByLocation($lat, $lng, $radius, $limit, $limitstart, Zend_Db_Table_Select $select = null)
	{
		if ($select === null)
		{
			$select = $this->select();
		}

		$comments_subselect = Application_Model_Comments::getInstance()->select()
			->from('comments', array('news_id', 'COUNT(*) as count'))
			->where('comments.isdeleted =?', 0)
			->group('news_id');

		$votings_subselect = Application_Model_Voting::getInstance()->select()
			->from('votings', array('news_id', 'news_count', 'COUNT(*) as count'))
			->where('votings.news_count =?', 1)
			->group('news_id');

		$result = $this->fetchAll(
			$select
				->setIntegrityCheck(false)
				->from($this, array(
					'news.*',
					'((IFNULL(votings.count, 0)+IFNULL(comments.count, 0)+1)/((IFNULL(TIMESTAMPDIFF(HOUR, created_date, NOW()), 0)+30)^1.1))*10000 as score',
					// https://developers.google.com/maps/articles/phpsqlsearch_v3#findnearsql
					'(3959 * acos(cos(radians(' . $lat . ')) * cos(radians(news.latitude)) * cos(radians(news.longitude) - ' .
						'radians(' . $lng . ')) + sin(radians(' . $lat . ')) * sin(radians(news.latitude)))) AS distance_from_source'
				))
				->where('news.latitude IS NOT NULL')
				->where('news.longitude IS NOT NULL')
				->where('news.isdeleted =?', 0)
				->joinLeft('user_data', 'news.user_id = user_data.id', array())
				->where('user_data.id IS NOT NULL')
				->joinLeft(array('comments' => new Zend_Db_Expr('(' . $comments_subselect . ')')), 'comments.news_id = news.id', array())
				->joinLeft(array('votings' => new Zend_Db_Expr('(' . $votings_subselect . ')')), 'votings.news_id = news.id', array())
				->having('distance_from_source < ' . $radius . ' OR distance_from_source IS NULL')
				->order('score DESC')
				->limit($limit, $limitstart)
		);

		return $result;
	}

	/**
	 * Finds news by location and user ID.
	 *
	 * @param	float	$lat
	 * @param	float	$lng
	 * @param	float	$radius
	 * @param	integer	$limit
	 * @param	integer	$limitstart
	 * @param	Application_Model_UserRow	$user
	 * @param	Zend_Db_Table_Select		$attributes
	 *
	 * @return	array
	 */
	public function findByLocationAndUser($lat, $lng, $radius, $limit, $limitstart, Application_Model_UserRow $user, Zend_Db_Table_Select $select = null)
	{
		if ($select === null)
		{
			$select = $this->select();
		}

		$select->where('news.user_id =?', $user->id);

		return $this->findByLocation($lat, $lng, $radius, $limit, $limitstart, $select);
	}

	/**
	 * Finds news in user friends by location.
	 *
	 * @param	float	$lat
	 * @param	float	$lng
	 * @param	float	$radius
	 * @param	integer	$limit
	 * @param	integer	$limitstart
	 * @param	Application_Model_UserRow	$user
	 * @param	Zend_Db_Table_Select		$attributes
	 *
	 * @return	array
	 */
	public function findByLocationInFriends($lat, $lng, $radius, $limit, $limitstart, Application_Model_UserRow $user, Zend_Db_Table_Select $select = null)
	{
		if ($select === null)
		{
			$select = $this->select();
		}

		$select->where('news.user_id <>?', $user->id);
		$select->where('news.user_id in (SELECT sender_id FROM friends WHERE reciever_id = ' . $user->id . ' AND status = "1")');
		$select->orWhere('news.user_id in (SELECT reciever_id FROM friends WHERE sender_id = ' . $user->id . ' AND status = "1")');

		return $this->findByLocation($lat, $lng, $radius, $limit, $limitstart, $select);
	}

	/**
	 * Finds news by location and user interests.
	 *
	 * @param	float	$lat
	 * @param	float	$lng
	 * @param	float	$radius
	 * @param	integer	$limit
	 * @param	integer	$limitstart
	 * @param	array	$interests
	 * @param	Zend_Db_Table_Select	$attributes
	 *
	 * @return	array
	 */
	public function findByLocationAndInterests($lat, $lng, $radius, $limit, $limitstart, array $interests, Zend_Db_Table_Select $select = null)
	{
		if (count($interests))
		{
			if ($select === null)
			{
				$select = $this->select();
			}

			$adapter = $this->getAdapter();

			foreach ($interests as &$_interest)
			{
				$_interest = 'news.news like ' . $adapter->quote('%' . $_interest . '%');
			}

			$select->where(implode(' OR ', $interests));
		}

		return $this->findByLocation($lat, $lng, $radius, $limit, $limitstart, $select);
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
}
