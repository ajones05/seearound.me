<?php

class My_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
	protected $_filters    = array();
	protected $_validators = array();

	public function validate(&$data) {
    	$filters    = $this->_filters;
    	$validators = $this->_validators;
    	
    	foreach (array_keys($filters) as $filterId) {
    		if (!isset($data[$filterId])) {
    			unset($filters[$filterId]);
    		}
    	}
    	
		foreach (array_keys($validators) as $validatorId) {
    		if (!isset($data[$validatorId])) {
    			unset($validators[$validatorId]);
    		}
    	}
    	
    	$input_filter = new Zend_Filter_Input($filters, $validators);
		$input_filter->setData($data);
		$input_filter->setOptions(array(
			'notEmptyMessage' => "This information is required."
		));
		
		if ($input_filter->isValid()) {
			$data = $input_filter->getEscaped();
			return true;
		} else {
			$errors = array();
			
			$unknown_fields = $input_filter->getInvalid();
			foreach ($unknown_fields as $field_name => $field_message) {
				$errors[$field_name] = implode(", ", (array) $field_message);
			}
			
			return $errors;
		}
    }
}
