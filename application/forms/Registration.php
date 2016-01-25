<?php

/**
 * Registration form class.
 */
class Application_Form_Registration extends Zend_Form
{
    /**
     * Initialize form.
     *
     * @return void
     */
    public function init()
    {
        $this->addElement(
			'text',
			'name',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 50))
				)
			)
		);

        $this->addElement(
			'text',
			'email',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('EmailAddress'),
					array('Db_NoRecordExists', false, array(
						'table' => 'user_data',
                        'field' => 'Email_id'
					))
				)
			)
		);

		$error_message = 'Password  minimum of 6 characters, with at least one character or number.';

        $this->addElement(
			'password',
			'password',
			array(
				'required' => true,
				'validators' => array(
					array('stringLength', false, array(
						'min' => 6,
						'max' => 20,
						'messages' => array(
							Zend_Validate_StringLength::INVALID => $error_message,
							Zend_Validate_StringLength::TOO_SHORT => $error_message,
							Zend_Validate_StringLength::TOO_LONG => $error_message
						)
					)),
					array('regex', false, array(
						'pattern' => '/[0-9a-z]+/',
						'messages' => array(
							Zend_Validate_Regex::INVALID => $error_message,
							Zend_Validate_Regex::NOT_MATCH => $error_message,
							Zend_Validate_Regex::ERROROUS => $error_message
						)
					))
				)
			)
		);
    }
}
