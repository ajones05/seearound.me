<?php
class Application_Model_EmailinvitesRow extends Zend_Db_Table_Row_Abstract
{
       
}

class Application_Model_Emailinvites extends Zend_Db_Table_Abstract
{
    protected $_name = "email_invites";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_EmailinvitesRow";
    protected $_instance = null;    
    public static function getInstance() 
    {
        if(null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    function getData($data = array(), $all = false) 
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
    
    function saveInvitationInfo($selData = array(), $insData = array())
    {
        $select = $this->select();
        $retVal = false;
        if($selData && is_array($selData)) {
            foreach ($selData as $key => $value) {
                $select->where($key.' =?',$value);
            }
        }
        if(!$row = $this->fetchRow($select)) {
            $this->createRow($insData)->save();
            $retVal = true;
        }
        return $retVal;
    }
    
    public function returnEmailInvites()
    {
        $select = $this->select()
                ->where("status='0'");
        return $this->fetchAll($select);  
    }
    
    public function updateInviteStatus($row)
    {
        $eiTable = new Application_Model_Emailinvites;
        if($row){
            $data = array(
                'sender_id' => Null,
                'receiver_email' => Null,
                'code' => $row->code,
                'self_email' => $row->self_email,
                'created' =>$row->created,
                'status' => 1
            );

            $where = $eiTable->getAdapter()->quoteInto('code = ?',$row->code);
            $eiTable->update($data, $where);	
        }
    }
    
}