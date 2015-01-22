<?php



class Application_Model_FriendsRow extends Zend_Db_Table_Row_Abstract 
{

    

}



class Application_Model_Friends extends Zend_Db_Table_Abstract {

    protected $_name     = 'friends';

    protected $_primary  = 'id';

    protected $_rowClass = 'Application_Model_FriendsRow';

    protected static $_instance = null;

    protected $_dependentTables = array();

    protected $_referenceMap    = array();



    public static function getInstance() {

        if (null === self::$_instance) {

            self::$_instance = new self();

        }

        return self::$_instance;

    }    



    public function validateData($request, &$data, &$errors, $type = null){



    }



    public function setData($data=array(), $check=array()) {

        $select = $this->select();

        $row = array();

        if(count($check) > 0) {

            foreach ($check as $index => $value) {

                $select->where($index." =?", $value);

            }

            if($row = $this->fetchRow($select)) {

                $row->setFromArray($data);

                $row->save();

            }else {

               $row = $this->createRow($data);

               $row->save();

            }

        }else {

            if(count($data)) {

                $row = $this->createRow($data);

                $row->save();

            }

        }

        return $row;

    }



    public function getdata($data=array(), $all=false, $limit=null, $offset=null, $order=array(), $group=null) {

        $select = $this->select();

        if(count($data) > 0){

            foreach($data as $index => $value) {

                $select->where($index." =?", $value);

            }

        }

        

        if($order) {

            $select->order($order['field']." ".$order['type']);

        }

        

        if($group) {

            $select->group($group);

        }

        

        if($limit) {

            if($offset) {

                $select->limit($limit, $offset);

            }else {

                $select->limit($limit);

            }

        }

        

        if($all) {

            return $this->fetchAll($select);

        }else {

            return $this->fetchRow($select);

        }

    }

    

    public function invite($data)

    {

        $select = $this->select()

            ->where("sender_id = ".$data['sender_id']." AND reciever_id = ".$data['reciever_id'])

            ->orWhere("sender_id = ".$data['reciever_id']." AND reciever_id = ".$data['sender_id']); //echo $select; exit;

        $row = $this->fetchAll($select);

        if(count($row) > 0) {

            return $row->toArray(); 

        }else {

            return $this->setData($data)->toArray();

        }

        

    }

    

    public function requester($data = array(), $all = false, $join = true)

    {

        if($join) {

            $select = $this->select()->setIntegrityCheck(false)

                ->from($this, array('fid'=> 'id', 'sender_id', 'reciever_id', 'status'))

                ->joinLeft('user_data', 'user_data.id = friends.sender_id') 

                ->order('user_data.Name');

        }else {

            $select = $this->select();

        }

        

        if(count($data) > 0) {

            foreach ($data as $index => $value) {

                $select->where($index." =?", $value);

            }

        } echo $select; exit; 

        

        if($all) {

            return $this->fetchAll($select);

        }else {

            return $this->fetchRow($select);

        }

        

    }

    

    public function getFriends($data = array(), $all = false) 

    {

        $select = $this->select();

        if(count($data) > 0) {

            foreach($data as $index => $value) {

                $select->where($index." =?", $value);

            }

        } 

        if($all) {

            return $this->fetchAll($select);

        }else {

            return $this->fetchRow($select);

        }

    }

    

    public function frendsList($userId = null, $status = false, $blocked=false, $limit=false) 

    {

        $select = $this->select()->setIntegrityCheck(false)

                ->from($this, array('fid'=>'id', 'sender_id','reciever_id', 'status'))

                ->joinLeft('user_data', 'friends.sender_id = user_data.id')

                ->joinLeft('address', 'user_data.id = address.user_id')

                ->where('friends.reciever_id =?', $userId);

                //->orWhere('friends.sender_id =?', $userId);

        if($status) {

            $select->where('friends.status =?', '0'); 

        }else {

            $select->where('friends.status !=?', '0'); 

        }

        if($blocked) {

            $select->where('friends.status !=?', '2');

        }

        

        if($limit) {

           $select->limit($limit); 

        }

                

        return $this->fetchAll($select);

    }

    

    public function getTotalFriends($user = 0, $limit = null, $offset = null) 

    {
        
        if($user) {

            $select1 = $this->select()->setIntegrityCheck(false)

                ->from($this, array('fid'=>'id', 'sender_id','reciever_id', 'status'))

                ->joinLeft('user_data', 'friends.sender_id = user_data.id', array('id', 'Name', 'Email_id', 'Profile_image', 'Network_id'))

                ->joinLeft('address', 'user_data.id = address.user_id', array('address', 'latitude', 'longitude'))

                ->where('friends.reciever_id =?', $user);

        

            $select2 = $this->select()->setIntegrityCheck(false)       

                ->from($this, array('fid'=>'id', 'sender_id','reciever_id', 'status'))

                ->joinLeft('user_data', 'friends.reciever_id = user_data.id', array('id', 'Name', 'Email_id', 'Profile_image', 'Network_id'))

                ->joinLeft('address', 'user_data.id = address.user_id', array('address', 'latitude', 'longitude'))

                ->where('friends.sender_id =?', $user);

            if($limit && $offset) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id LIMIT ".$limit." OFFSET ".$offset));

            } else if($limit) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id LIMIT ".$limit));

            } else if($offset) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id OFFSET ".$offset));

            } else {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id"));

            }

            if($row = $this->fetchAll($select)) {

                return $row;

            }

        }   

    }
    
    
    /*Added on 19/8/2013 for API */
    public function getIndividualFriendsWs($user = 0, $targetFriendId , $limit = null, $offset = null){
        if($user) {

            $select1 = $this->select()->setIntegrityCheck(false)

                ->from($this, array('fid'=>'id', 'sender_id','reciever_id', 'status'))

                ->joinLeft('user_data', 'friends.sender_id = user_data.id', array('id', 'Name', 'Email_id', 'Profile_image', 'Network_id','Birth_Date'))

                ->joinLeft('address', 'user_data.id = address.user_id', array('address', 'latitude', 'longitude'))
                
                ->joinLeft('user_profile', 'user_data.id = user_profile.user_id', array('Activities', 'Gender'))

                ->where('friends.reciever_id =?', $user);

        

            $select2 = $this->select()->setIntegrityCheck(false)       

                ->from($this, array('fid'=>'id', 'sender_id','reciever_id', 'status'))

                ->joinLeft('user_data', 'friends.reciever_id = user_data.id', array('id', 'Name', 'Email_id', 'Profile_image', 'Network_id','Birth_Date'))

                ->joinLeft('address', 'user_data.id = address.user_id', array('address', 'latitude', 'longitude'))
                
                ->joinLeft('user_profile', 'user_data.id = user_profile.user_id', array('Activities', 'Gender'))

                ->where('friends.sender_id =?', $user);

            if($limit && $offset) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' AND A.id= ".$targetFriendId." GROUP BY A.id LIMIT  ORDER BY A.Name ".$limit." OFFSET ".$offset));

            } else if($limit) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' AND A.id=".$targetFriendId." GROUP BY A.id LIMIT  ORDER BY A.Name ".$limit));

            } else if($offset) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' AND A.id=".$targetFriendId." GROUP BY A.id OFFSET  ORDER BY A.Name ".$offset));

            } else {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' AND A.id=".$targetFriendId."  GROUP BY A.id  ORDER BY A.Name"));

            }
        
          
            if($row = $this->fetchAll($select)) {

                return $row;

            }

        }   

    }



    /*Added on 19/8/2013 for API */
    public function getTotalFriendsListWs($user = 0,  $limit = null, $offset = null){
        if($user) {

            $select1 = $this->select()->setIntegrityCheck(false)

                ->from($this, array('status'))

                ->joinLeft('user_data', 'friends.sender_id = user_data.id', array('id', 'Name','Profile_image'))
        
                ->where('friends.reciever_id =?', $user);

            $select2 = $this->select()->setIntegrityCheck(false)       

                ->from($this, array('status'))
                
                ->joinLeft('user_data', 'friends.reciever_id = user_data.id', array('id', 'Name','Profile_image'))
       
                ->where('friends.sender_id =?', $user);

            if($limit && $offset) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id LIMIT  ORDER BY A.Name ".$limit." OFFSET ".$offset));

            } else if($limit) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id LIMIT  ORDER BY A.Name ".$limit));

            } else if($offset) {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id OFFSET  ORDER BY A.Name ".$offset));

            } else {

                $select = $this->select()->union(array("SELECT * FROM (".$select2, $select1.") as A WHERE A.status ='1' GROUP BY A.id  ORDER BY A.Name"));

            }

            
            if($row = $this->fetchAll($select)) {

                return $row;

            }

        }   

    }

    

    public function getStatus($cuser, $rueser) 

    {

        $select = $this->select()

                ->where("sender_id =".$cuser." AND reciever_id =".$rueser)

                ->orWhere("sender_id =".$rueser." AND reciever_id =".$cuser); //echo $select; exit;

        return $this->fetchRow($select);

        

    }

        

}