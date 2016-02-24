<?php
/**
 * User password form class.
 */
class Application_Form_UserPassword extends Zend_Form
{
    /**
     * Initialize form.
     *
     * @return void
     */
    public function init()
    {
		$error_message = 'Password  minimum of 6 characters, with at least one character or number.';

        $this->addElement(
			'password',
			'password', [
				'required' => true,
				'validators' => [
					['stringLength', false, [
						'min' => 6,
						'max' => 20,
						'messages' => [
							Zend_Validate_StringLength::INVALID => $error_message,
							Zend_Validate_StringLength::TOO_SHORT => $error_message,
							Zend_Validate_StringLength::TOO_LONG => $error_message
						]
					]],
					['regex', false, [
						'pattern' => '/[0-9a-z]+/',
						'messages' => [
							Zend_Validate_Regex::INVALID => $error_message,
							Zend_Validate_Regex::NOT_MATCH => $error_message,
							Zend_Validate_Regex::ERROROUS => $error_message
						]
					]]
				]
			]
		);

		$this->addElement(
			'password',
			're-password', [
				'required' => true,
				'validators' => [
					['identical', false, ['token' => 'password']]
				]
			]
		);
    }
}
