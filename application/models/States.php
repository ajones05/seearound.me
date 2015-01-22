<?php
class Application_Model_States extends Zend_Db_Table_Abstract {

 	protected $_name     = 'states';
	protected $_primary  = array('id');
    //protected $_rowClass = 'Application_Model_CampaignRow';
		
	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}		
		return self::$_instance;
	}
}

?>