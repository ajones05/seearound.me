<?php

class Application_Model_NewsRow extends My_Db_Table_Row_Abstract {
    
}

class Application_Model_News extends Zend_Db_Table_Abstract {

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
    
    
    
}
?>