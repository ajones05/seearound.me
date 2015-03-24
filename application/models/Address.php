<?php
class Application_Model_Address extends Zend_Db_Table_Abstract {

 	protected $_name     = 'address';
	protected $_primary  = array('Id');
	protected static $_instance = null;

    protected $_referenceMap = array(
		'User' => array(
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		)
    );

	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}		
		return self::$_instance;
	}
    
    public function saveAddress($addressData,$addressRow) {
        $addressTable = new Application_Model_Address();
        if($addressRow){
            $addressRow->setFromArray($addressData);
        } else {
        	$auth = Zend_Auth::getInstance()->getStorage()->read();
        	$addressData['user_id'] = $auth['user_id'];
            $addressRow = $addressTable->createRow($addressData);
        }
        $addressRow->save();
        return $addressRow;
    }
    public function searchRow($field,$valueToSearch) {
        $addressTable = new Application_Model_Address();
        $condition = $field." = '".$valueToSearch."'";
        $select = $addressTable->select()
            ->where($condition);
        $addressRow = $addressTable->fetchRow($select);
        return $addressRow;
    }
}
?>