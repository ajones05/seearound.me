<?php
/**
 * Forgot password form class.
 */
class Application_Form_Forgot extends Zend_Form
{
	/**
	 * Initialize form.
	 *
	 * @return void
	 */
	public function init()
	{
		$this->addElement('text', 'email', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['EmailAddress']]
		]);
	}
}
