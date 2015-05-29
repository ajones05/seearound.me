<?php

class Application_Model_VotingRow extends Zend_Db_Table_Abstract {
    
}

class Application_Model_Voting extends My_Db_Table_Abstract
{
	/**
	 * @var	Application_Model_Voting
	 */
	protected static $_instance;

    protected $_name = 'votings';
    protected $_primary = array('id');

    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /* function for measure score of post based on like and posted time */
     //((votes+comments+1)/((hours+30)^1.1))
    function measureLikeScore($action, $action_id, $userid) {
        $votingTable  = new Application_Model_Voting();
        $newsTable    = new Application_Model_News();
        $totalLike    = $votingTable->getTotalVoteCounts($action, $action_id, $userid);
        $totalComment = $votingTable->getTotalCommentsCounts($action, $action_id, $userid);
        $postcreatedTime     = $votingTable->getPostTime($action_id);
        $t1 = StrToTime (date("Y-m-d H:i:s"));
        $t2 = StrToTime ($postcreatedTime);
        $diff = $t1 - $t2;
        $hours = $diff / ( 60 * 60 );
        $postTime = $hours;
        $a = ($postTime + 30);
        $b = 1.1;
        $postPower = pow($a, $b);
        $totalLike = ($totalLike +$totalComment+ 1);
        $score = $totalLike / $postPower;       /* (votes+1) / ((time+2)^1.2) */
        $score =number_format($score,5,'.','');  //format score value to take exactly 5 places after point.
       
        if($score) {
          $insData = array(
          'score'  => $score
          );
          $newsTable->update($insData, $newsTable->getAdapter()->quoteInto("id =?", $action_id));
          //$row->save();
        } 
          
    }
    
    /*
    
    function measureLikeScore($action, $action_id, $userid) {
        $votingTable  = new Application_Model_Voting();
        $newsTable    = new Application_Model_News();
        $totalLike    = $votingTable->getTotalVoteCounts($action, $action_id, $userid);
        $postcreatedTime     = $votingTable->getPostTime($action_id);
        $t1 = StrToTime (date("Y-m-d H:i:s"));
        $t2 = StrToTime ($postcreatedTime);
        $diff = $t1 - $t2;
        $hours = $diff / ( 60 * 60 );
        $postTime = $hours;
        $a = ($postTime + 2);
        $b = 1.2;
        $postPower = pow($a, $b);
        $totalLike = ($totalLike + 1);
        $score = $totalLike / $postPower;       
        $score =number_format($score,5,'.','');  //format score value to take exactly 5 places after point.
     
        if($score) {
          $insData = array(
          'score'  => $score
          );
          $newsTable->update($insData, $newsTable->getAdapter()->quoteInto("id =?", $action_id));
          //$row->save();
   
          } 
          
    } */
    

    /* function end */


    /* function for inserting score of post */

    function insertNewsScore($score, $news_id) {
        echo "before update";
        exit;
        $newsTable = new Application_Model_News();
        $data = array(
            'score' => $score
        );
        $where['id = ?'] = $news_id;
        $newsTable->update($data, $where);
    }

    function getPostTime($news_id) {
        $newsTable = new Application_Model_News();
        $sel = $newsTable->select()->from('news', array('created_date'))
                ->where('id=?', $news_id)->where('isdeleted =?', 0);
        $fetch = $newsTable->fetchRow($sel);
        return $fetch->created_date;
        exit;
    }

    /* getPostTime function ends  */

    /* @code for save voting data
      @created by :D
      @date       :27-12-2012
     */

    public function saveVotingData($action, $action_id, $userid) {
        $votingTable = new Application_Model_Voting();
        $select = $this->select()->from('votings', array('*'))
                ->where('user_id=?', $userid)
                ->where('news_id=?', $action_id);
        $fetch = $votingTable->fetchRow($select);

        if ($fetch) {
            return $fetch->id;
        }
        $yes = 1;
        $no = 0;
        if ($action == 'news') {
            $newsdata = array(
                'type' => $action,
                'user_id' => $userid,
                'news_id' => $action_id,
                'news_count' => $yes,
                'type' => 'news'
            );
            $row = $votingTable->createRow($newsdata);

            $row->save();

            //return $row->id;
        }
        if ($action == 'comments') {
            $commentsdata = array(
                'type' => $action,
                'user_id' => $userid,
                'comments_id' => $action_id,
                'comments_count' => $yes,
                'type' => 'comments'
            );
            $row = $votingTable->createRow($commentsdata);
            $row->save();
            //return $row->id;
        }
    }

    /* saveVotingData function ends */

    public function totalcountVoting($action, $action_id, $user_id) {
        $votingTable = new Application_Model_Voting();
        $yes = 1;
        $no = 0;
        if ($action == 'news') {
            $sel = $votingTable->select()->from('voting', array('*', 'count(*) as total'))
                    ->where('news_count=?', '1')
                    ->where('news_id=?', $action_id);
            $fetch = $votingTable->fetchAll($sel);
            return $fetch->total;
        } else if ($action == 'comments') {
            $sel = $votingTable->select()->from('votings', array('*', 'count(*) as total'))
                    ->where('comments_count=?', '1')
                    ->where('comments_id=?', $action_id);
            $fetch = $votingTable->fetchAll($sel);
            return $fetch->total;
        }
    }

    public function existingVoters($action, $action_id, $user_id) {
        $votingTable = new Application_Model_Voting();
        if ($action == 'news') {
            $sel = $votingTable->select()->from('votings', array('*'))
                    ->where('user_id=?', $user_id)
                    ->where('news_id=?', $action_id);
            $fetch = $votingTable->fetchRow($sel);
            return $fetch->news_id;
        }
    }

    public function allVoters($user_id) {
        $votingTable = new Application_Model_Voting();
        $sel = $votingTable->select()->from('votings', array('*'))
                ->where('user_id=?', $user_id);
        $fetch = $votingTable->fetchAll($sel);
        return $fetch;
    }

    public function checkExistUser($action_id, $userid) {
        $votingTable = new Application_Model_Voting();
        $sel = $votingTable->select()->from('votings', array('*'))
                ->where('user_id=?', $user_id)
                ->where('news_id=?', $action_id);
        $fetch = $votingTable->fetchRow($sel);
        return $fetch->type;
    }

    public function getTotalVoteCounts($action_id, $news_id, $user_id) {
        $news_count = 1;
        if ($action_id == 'news') {
            $votingTable = new Application_Model_Voting();
            $sel = $votingTable->select()->from('votings', array('*'))
                            ->where('news_id=?', $news_id)->where('news_count=?', $news_count);
            $fetch = $votingTable->fetchAll($sel);
            return count($fetch);
        }
    }

	public function getTotalCommentsCounts($action_id, $news_id, $user_id)
	{
		if ($action_id == 'news')
		{
			return Application_Model_Comments::getInstance()->getCountByNewsId($news_id);
		}
	}

	/**
	 * Returns voiting count by news ID.
	 *
	 * @param	integer	$news_id
	 *
	 * @return	integer
	 */
    public function findCountByNewsId($news_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, 'COUNT(*) as voting_count')
				->where('news_id=?', $news_id)
				->where('news_count=?', 1)
		);

		return $result->voting_count;
    }

    function firstNewsExistence($action, $action_id, $userid) {
        $votingTable = new Application_Model_Voting();
        $data = array(
            'user_id' => $userid,
            'news_id' => $action_id,
            'news_count' => 0,
            'type' => $action,
        );

        try {
            $row = $votingTable->createRow($data);
            $row->save();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
        return $row->id;
    }

	/**
	 * Checks if news is liked by user.
	 *
	 * @param	integer	$news_id
	 * @param	integer	$user_id
	 * @param	integer	$news_count
	 *
	 * @return	string
	 */
	public function findNewsLikeByUserId($news_id, $user_id, $news_count)
	{
        $result = $this->fetchRow(
			$this->select()
                ->where('user_id=?', $user_id)
                ->where('news_id=?', $news_id)
                ->where('news_count=?', $news_count)
		);

		return $result;
	}
}
