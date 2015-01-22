<?php
class Application_Model_Countries extends Zend_Db_Table_Abstract {

 	protected $_name     = 'countries';
	protected $_primary  = array('id');
		
	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}		
		return self::$_instance;
	}
}

?>