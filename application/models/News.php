<?php

class Application_Model_NewsRow extends My_Db_Table_Row_Abstract {
    
}

class Application_Model_News extends Zend_Db_Table_Abstract {

	/**
	 * @var	Application_Model_News
	 */
	protected static $_instance;

    protected $_name     = 'news';
    protected $_primary  = array('id');
    protected $_rowClass = 'Application_Model_NewsRow';
    protected $_dependentTables = array('Application_Model_Comments');
    protected $_referenceMap    = array(
            'User' => array(
            'columns' => 'user_id',
            'refTableClass' => 'Application_Model_User',
            'refColumns' => 'id',
            'onDelete' => self::CASCADE
        )
    );    

    public static function getInstance() 
    {
            if (null === self::$_instance) {
                    self::$_instance = new self();
            }		
            return self::$_instance;
    }
    public function getNews($data = array(), $all = false) 
    {
      $select = $this->select();
      if(count($data) > 0) {
         foreach($data as $index => $value) {
                $select->where($index. " =?", $value);
          }
      } 
      
      /* 
                ->from('user_data')
                ->joinLeft('address','address.user_id = user_data.id',array('latitude','longitude'))
                ->where('Network_id =?', $data['Network_id']); //echo $select; exit;
       */
        if($all) {
            return $this->fetchAll($select);
        }else {
             //$getResult = ($this->fetchRow($select));
             
            // echo "<pre>"; print_r($getResult); exit;
             //echo "<pre>"; print_r ($this->fetchRow($select)); exit;
            return $this->fetchRow($select);
        }
    }

    public function getNewsWithDetails($id = 0) 
    {
        if($id) {
            $select = $this->select()->setIntegrityCheck(false)
                    ->from($this)
                    ->joinLeft('user_data', 'news.user_id = user_data.id', array(email => 'Email_id', name => 'Name'))
                    ->joinLeft('user_profile', 'news.user_id = user_profile.user_id')
                    ->joinLeft('address', 'news.user_id = address.user_id')
                    ->where('news.id =?', $id);
            return $this->fetchRow($select);
        }
    }
    
   public function existUserId($news_id,$user_id){
        $newsTable = new Application_Model_News();
        $select = $this->select()->from('news',array('user_id'))
                                      ->where('id=?', $news_id)
                                      ->where('user_id=?', $user_id);
        $fetch  = $newsTable->fetchRow($select);
        return $fetch['user_id'];
    }

    function selectLatestNewsId($userId){
        $newsTable = new Application_Model_News();
        $select    = $newsTable->select()->from('news',array('id,user_id'));
        $fetch     = $newsTable->fetchAll($select);
       
        return $fetch;
    }
    
    
    //((votes+comments+1)/((hours+30)^1.1))
    function manipulateDb() {
        $newsTable = new Application_Model_News();
        $votingTable = new Application_Model_Voting();
        $select = $newsTable->select()->from('news', array('*'));
        $fetch = $newsTable->fetchAll($select);
        $response = $fetch->toArray();
     
        foreach ($response as $key => $row) {
          
            
            if ($response[$key]['score'] == '') {
               
                $totalLikeCounts = 0;
                $type = 'news';
                $totalCommentsCounts = $votingTable->getTotalCommentsCounts($type, $response[$key]['id'], $response[$key]['user_id']);
                $createdDate = StrToTime($response[$key]['created_date']);
                $currentDate = StrToTime(date("Y-m-d H:i:s"));
                
                $timeDiffernce = ($currentDate - $createdDate);
                $timeDiffernce = $timeDiffernce / 3600;
                $numerator = ($totalLikeCounts + $totalCommentsCounts + 1);
                $demonator = pow(($timeDiffernce + 30), 1.1);
                $score = $numerator / $demonator;
                $score = number_format($score, 5, '.', '');
                
                if ($score) {
                    $insData = array(
                        'score' => $score
                    );
                    $newsTable->update($insData, $newsTable->getAdapter()->quoteInto("id =?", $response[$key]['id']));
                }
            }  else {

                $type = 'news';
                $totalLikeCounts = $votingTable->getTotalVoteCounts($type, $response[$key]['id'], $response[$key]['user_id']);
                $totalCommentsCounts = $votingTable->getTotalCommentsCounts($type, $response[$key]['id'], $response[$key]['user_id']);
                
                $createdDate = StrToTime($response[$key]['created_date']);
                $currentDate = StrToTime(date("Y-m-d H:i:s"));
                $timeDiffernce = ($currentDate - $createdDate);
                $timeDiffernce = $timeDiffernce / 3600;
                $numerator = ($totalLikeCounts + $totalCommentsCounts + 1);
                $demonator = pow(($timeDiffernce + 30), 1.1);
                $score = $numerator / $demonator;
                $score = number_format($score, 5, '.', '');
            
                if ($score) {
               
                    $insData = array(
                        'score' => $score
                    );
                  echo "<pre>"; print_r($insData)."<br/>";
                  $newsTable->update($insData, $newsTable->getAdapter()->quoteInto("id =?", $response[$key]['id']));
                } 
            } 
        }
        return $fetch;
    }
    
    
   /* 
    function manipulateDb(){
       
        $newsTable = new Application_Model_News();
        $votingTable  = new Application_Model_Voting();
        $select    = $newsTable->select()->from('news',array('*'));
        $fetch     = $newsTable->fetchAll($select);
        $response  = $fetch->toArray();
       
        foreach($response as $key=>$row){
        if($response[$key]['score']==''){
             $totalLikeCounts = 0;
             $createdDate  =  StrToTime ($response[$key]['created_date']);
             $currentDate  =  StrToTime (date("Y-m-d H:i:s"));
             $timeDiffernce =  ($currentDate-$createdDate);
             $timeDiffernce = $timeDiffernce/3600;
             $numerator =  ($totalLikeCounts+1);
             $demonator =   pow(($timeDiffernce+2),1.2);
             $score  =  $numerator/$demonator;   
             $score = number_format($score,5,'.','');  
             if($score) {
              $insData = array(
              'score'  => $score
              );
              $newsTable->update($insData, $newsTable->getAdapter()->quoteInto("id =?", $response[$key]['id']));
              } 
           } else {
              $type='news';
              $totalLikeCounts = $votingTable->getTotalVoteCounts($type, $response[$key]['id'],$response[$key]['user_id']);
              $createdDate = StrToTime ($response[$key]['created_date']);
              $currentDate = StrToTime (date("Y-m-d H:i:s"));
              $timeDiffernce = ($currentDate-$createdDate);
              $timeDiffernce = $timeDiffernce/3600;
              $numerator = ($totalLikeCounts+1);
              $demonator = pow(($timeDiffernce+2),1.2);
              $score = $numerator/$demonator;   
              $score = number_format($score,5,'.','');  
              if($score) {
               $insData = array(
               'score'  => $score
               );
              $newsTable->update($insData, $newsTable->getAdapter()->quoteInto("id =?", $response[$key]['id']));
              } 
           }
        
        }
        return $fetch;
    } */

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
	 *
	 * @return	boolean
	 */
    public static function checkId($news_id, &$news)
    {
		if ($news_id == null)
		{
			return false;
		}

		$db = self::getInstance();

		$news = $db->fetchRow($db->select()->where('id =?', $news_id));

		return $news != null;
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
