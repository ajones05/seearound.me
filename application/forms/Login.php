<?php
use Respect\Validation\Validator as v;

/**
 * Login form class.
 */
class Application_Form_Login extends Zend_Form
{
	/**
	 * Initialize form.
	 */
	public function init()
	{
		$this->addElement('text', 'email', [
			'required' => true,
			'validators' => [['Callback', false, v::email()]]
		]);

		$this->addElement('password', 'password', [
			'required' => true
		]);

		$this->addElement('checkbox', 'remember', [
			'default' => 0,
			'required' => false
		]);
	}
}
