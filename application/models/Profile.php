<?php
class Application_Model_Profile extends Zend_Db_Table_Abstract {

 	protected $_name     = 'user_profile';
	protected $_primary  = array('id');
    		
	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}		
		return self::$_instance;
	}
}
?>