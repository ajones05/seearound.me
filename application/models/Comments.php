<?php



class Application_Model_CommentsRow extends My_Db_Table_Row_Abstract {

    

}



class Application_Model_Comments extends Zend_Db_Table_Abstract {



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

        

    function getAllCommentUsers($newsId = null)
    {

        if($newsId) {

            $select = $this->select()->setIntegrityCheck(false)

                    ->from($this)

                    ->joinLeft('user_data', 'comments.user_id = user_data.id', array(email=>'Email_id', name => 'Name'))

                    ->where('comments.news_id =?', $newsId)

                    ->group('comments.user_id'); 

            return $this->fetchAll($select);

        } 

    }
    
    public function getCommentOfAllNews(){
        $select = $this->select()
                  ->from('comments', array('news_id','count(*) as comment_count'))
                  ->group('news_id');
        return $this->fetchAll($select); 
         
    }
    
    public function getCommentCountOfNews($newsId = null){
       $select = $this->select()
                  ->from('comments', array('count(*) as comment_count'))
                  ->where('news_id=?', $newsId);
       $resultSet = $this->fetchAll($select); 
       $resultSet = $resultSet->toArray();
       if($resultSet){
        return $resultSet[0]['comment_count'];
       } else {
        return 0;
       }
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
  
}



?>