<?php

class Application_Model_UserProfile extends Zend_Db_Table_Abstract
{
    protected $_name = 'user_profile';

    protected $_instance = null;

    public static function getInstance()
    {
        if(null === self::$_instance)
        {
            self::$_instance = new self();    
        }    
    }
    
    public function search($data){
        $select = parent::select()
            ->where('Address LIKE ', '%'.$data.'%')
            ->where('City LIKE', '%'.$data.'%')
            ->where('Zipcode LIKE', '%'.$data.'%');
        $result = parent::fetchAll($select);
        return $result;
    }
}

