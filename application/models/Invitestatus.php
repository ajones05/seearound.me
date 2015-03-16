<?php
class Application_Model_InvitestatusRow extends My_Db_Table_Row_Abstract
{
       
}

class Application_Model_Invitestatus extends My_Db_Table_Abstract
{
    protected $_name = "invite_status";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_InvitestatusRow";
    protected static $_instance = null;    
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
    
    function setData() 
    {
        
    }
    
}