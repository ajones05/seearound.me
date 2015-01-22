<?php
class Application_Model_MessageReplyRow extends My_Db_Table_Row_Abstract
{
       
}

class Application_Model_MessageReply extends My_Db_Table_Abstract
{
    protected $_name = "message_reply";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_MessageReplyRow";
    protected $_instance = null;    
    public static function getInstance() 
    {
        if(null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    function getData($data = array(), $all=null) 
    {
        $select = $this->select();
        if($data && is_array($data)) {
            foreach ($data as $index => $value) {
                $select->where($index.' =?', $value);
            } 
        } 
        if($all) {
            return $this->fetchAll($select);
        } else {
            return $this->fetchRow($select);
        }
    }
    
    function replyWithUserData($data = array(), $all=false, $limit=5, $offset=0) 
    {
        $select = $this->select()->setIntegrityCheck(false) 
                ->from($this)
                ->join('user_data', "message_reply.sender_id = user_data.id", array("name","user_id"=>"id"));
        if($data && is_array($data)) {
            foreach ($data as $key => $value) {
                $select->where($key."=?",$value);
            }
        }
        $select->order('message_reply.created');
        if(!$all) {
            $select->limit($limit, $offset);
        }
        return $this->fetchAll($select);
    }
    
    function replyViewed($rowId, $user_id)
    {
        $select = $this->select()
                ->where("message_id =?", $rowId);
        if($rows = $this->fetchAll($select)) {
            foreach ($rows as $row) {
                if($row->receiver_id == $user_id) {
                    $row->receiver_read = "true";
                    $row->save();
                }
            }
        }
        return $this->fetchAll($select);
    }
    
    function setData() 
    {
        
    }
    
}