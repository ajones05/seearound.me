<?php
/**
 * User password form class.
 */
class Application_Form_UserPassword extends Zend_Form
{
	/**
	 * Initialize form.
	 */
	public function init()
	{
		$this->addElement('password', 'password', [
			'required' => true,
			'validators' => self::passwordValidators()
		]);

		$this->addElement('password', 're-password', [
			'required' => true,
			'validators' => [['identical', false, ['token' => 'password']]]
		]);
	}

	/**
	 * Returns password validators.
	 *
	 * @return array
	 */
	public static function passwordValidators()
	{
		$message = 'Password  minimum of 6 characters, ' .
			'with at least one character or number.';

		return [
			['stringLength', false, [
				'min' => 6,
				'max' => 20,
				'messages' => [
					Zend_Validate_StringLength::INVALID => $message,
					Zend_Validate_StringLength::TOO_SHORT => $message,
					Zend_Validate_StringLength::TOO_LONG => $message
				]
			]],
			['regex', false, [
				'pattern' => '/[0-9a-z]+/',
				'messages' => [
					Zend_Validate_Regex::INVALID => $message,
					Zend_Validate_Regex::NOT_MATCH => $message,
					Zend_Validate_Regex::ERROROUS => $message
				]
			]]
		];
	}
}
