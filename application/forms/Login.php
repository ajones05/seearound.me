<?php

/**
 * Login form class.
 */
class Application_Form_Login extends Zend_Form
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
			'email',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('EmailAddress')
				)
			)
		);

        $this->addElement(
			'password',
			'password',
			array(
				'required' => true
			)
		);
	}
}
