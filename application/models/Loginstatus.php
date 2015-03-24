<?php
class Application_Model_LoginstatusRow extends My_Db_Table_Row_Abstract
{
       
}

class Application_Model_Loginstatus extends My_Db_Table_Abstract
{
    protected $_name = "login_status";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_LoginstatusRow";
    protected static $_instance = null;    
    public static function getInstance() 
    {
        if(null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    function getData($data = array(), $all = null) 
    {
        $select = $this->select();
        if($data && is_array($data)) {
            foreach ($data as $index => $value) {
                $select->where($index.' =>', $value);
            }            
        }
        
        if($all) {
            return $this->fetchAll($select);
        } else {
            return $this->fetchrRow($select);
        }
    }
    
    function sevenDaysOldData($user_id) 
    {
        $select = $this->select()
                ->where('user_id =?', $user_id)
                ->where('login_time >= CURRENT_DATE() - INTERVAL 7 DAY');
        return $this->fetchAll($select);
    }
    
    function setData($data = array()) 
    {
        if($data) {
            if(in_array('id', $data)) {
                $where = $this->getDefaultAdapter()->quoteInto('id', $data['id']);
                unset($data['id']);
                $row = $this->update($data, $where);
            } else {
                $row = $this->createRow($data);
                $row->save();
            }
        }
        return $row;
    }
    
}