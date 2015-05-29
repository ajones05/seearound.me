<?php 

class Application_Model_FbtsempusersRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_Fbtempusers extends Zend_Db_Table_Abstract {
    
    protected $_name = "facebook_temp_users";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_FbtsempusersRow";
    protected static $_instance = null;
    
    public static function getInstance() 
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }   

    public function invite($data = array()) 
    {
        $select = $this->select();
        if(count($data) > 0) {
            foreach($data as $index => $value) {
                $select->where($index . " =?", $value);
            }
        }
        if($row = $this->fetchRow($select)) {
            return $row->toArray();
        }else {
            $data['cdate'] = date('Y-m-d H:i:s');
            $data['udate'] = date('Y-m-d H:i:s');
            $row = $this->createRow($data);
            $row->save();
            return $row->toArray();
        }
    }

	/**
	 * Finds records by receiver network ID.
	 *
	 * @param	string	$network_id
	 * @param	integer	$sender_id
	 *
	 * return	array
	 */	
	public static function findAllByNetworkId($network_id, $sender_id = null)
	{
		$db = self::getInstance();
		$query = $db->select()->where('reciever_nw_id =?', $network_id);

		if ($sender_id)
		{
			$query->where('sender_id =?', $sender_id);
		}

		$result = $db->fetchAll($query);

		return $result;
	}
}
