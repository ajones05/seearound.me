<?php

class My_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
	protected $_filters    = array();
	protected $_validators = array();
    
	protected $loaded = array();

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
    
    public function setValidatorHaystack($haystacks) {
    	foreach ($haystacks as $name=>$value) {
	    	if (isset($this->_validators[$name]) && ($this->_validators[$name][0][0] == 'InArray')) {
	    		$this->_validators[$name][0][1] = $value;
	    	}
    	}
    }

    /**
     * Cached load of row
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract
     */
    public function findRow($id) {
        if (isset($this->loaded[$id])) {
            return $this->loaded[$id];
        }
        
        $row = $this->find($id)->current();
        if ($row !== null) {
            $this->loaded[$id] = $row;
        }

        return $row;
    }

}