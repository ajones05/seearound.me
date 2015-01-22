<?php

abstract class My_Db_Table_Row_Abstract extends Zend_Db_Table_Row_Abstract {
	
	protected function _postInsert() {
		parent::_postInsert();
		
		// Auditing
		//Default_Model_Audit::logInsert($this->getTable()->info(), $this->_data);
	}
	
	protected function _postUpdate() {
		parent::_postUpdate();
		
		// Auditing
		//Default_Model_Audit::logUpdate($this->getTable()->info(), $this->_cleanData, $this->_data);
	}
	
	protected function _postDelete() {
		parent::_postUpdate();
		
		// Auditing
		//Default_Model_Audit::logDelete($this->getTable()->info(), $this->_cleanData);
	}
}