<?php



class Application_Model_CommentsRow extends My_Db_Table_Row_Abstract {

    

}



class Application_Model_Comments extends Zend_Db_Table_Abstract
{
	/**
	 * @var	Application_Model_Comments
	 */
	protected static $_instance;

	/**
	 * @var	integer
	 */
	public $news_limit = 30;

 	protected $_name     = 'comments';

	protected $_primary  = array('id');

        protected $_rowClass = 'Application_Model_CommentsRow';

        protected $_dependentTables = array();

        protected $_referenceMap    = array(

            'News' => array(

            'columns' => 'news_id',

            'refTableClass' => 'Application_Model_News',

            'refColumns' => 'id',

            'onDelete' => self::CASCADE

            )

        );   

		

	public static function getInstance() {

		if (null === self::$_instance) {

			self::$_instance = new self();

		}		

		return self::$_instance;

	}

        

	/**
	 * Returns notify
	 *
	 *
	 *
	 * @reutrn	array
	 */
    public function getAllCommentUsers($news_id, array $except_user_ids)
    {
		$select = $this->select()
			->setIntegrityCheck(false)
			->from($this, 'user_data.*')
			->joinLeft('user_data', 'comments.user_id = user_data.id')
			->where('comments.news_id =?', $news_id)
			->group('comments.user_id'); 

		foreach ($except_user_ids as $user_id)
		{
			$select->where('comments.user_id <>?', $user_id);
		}

		return $this->fetchAll($select);
    }
    
    public function getCommentOfAllNews(){
        $select = $this->select()
                  ->from('comments', array('news_id','count(*) as comment_count'))
                  ->group('news_id');
        return $this->fetchAll($select); 
         
    }

	/**
	 * Returns comments count by news ID.
	 *
	 * @param	integer	$news_id
	 *
	 * @return	integer
	 */
	public function getCountByNewsId($news_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from('comments', array('count(*) as comment_count'))
				->where('news_id=?', $news_id)
		); 

		if ($result)
		{
			return $result->comment_count;
		}

		return 0;
	}

   public function getCommentsByUser($newsId,$userId) {
        $select = $this->select()
                  ->from('comments', array('news_id'))
                  ->where('user_id=?', $userId)
                  ->where('news_id=?', $newsId)
                  ->where('isdeleted!=?', 1);
        $resultSet =  $this->fetchAll($select);
        $resultSet = $resultSet->toArray();
        if($resultSet) {
            return "Yes";
        }   else {
             return "No";
        }
   }

	/**
	 * Finds records by news ID.
	 *
	 * @param	integer	$news_id
	 * @param	integer	$limit
	 * @param	integer	$limitstart
	 *
	 * @return	array
	 */
	public function findAllByNewsId($news_id, $limit, $limitstart = 0)
	{
		return $this->fetchAll(
			$this->select()
				->from($this, 'comments.*')
				->where('news_id=?', $news_id)
				->where('isdeleted!=?', 1)
				->joinLeft('user_data', 'comments.user_id = user_data.id', '')
				->where('user_data.id IS NOT NULL')
				->where('user_data.status =?', 'active')
				->order('comments.id DESC')
				->group('comments.id')
				->limit($limit, $limitstart)
		);
	}

	public static function viewMoreLabel($count, $limit = 30)
	{
		$label = 'View ';

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
     * Inserts a new row.
     *
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
    public function insert(array $data)
    {
		$data['updated_at'] = date('Y-m-d H:i:s');

		return parent::insert($data);
	}
}
