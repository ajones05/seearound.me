<?php
/**
 * Message form class.
 */
class Application_Form_Message extends Zend_Form
{
	/**
	 * Initialize form.
	 *
	 * @return void
	 */
	public function init()
	{
		$this->addElement('text', 'subject', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['stringLength', false, [1, 250]]]
		]);

		$this->addElement('text', 'message', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['stringLength', false, [1, 65535]]]
		]);
	}
}
