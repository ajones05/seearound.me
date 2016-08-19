<?php
/**
 * Comment form class.
 */
class Application_Form_Comment extends Zend_Form
{
	/**
	 * Initialize form (used by extending classes)
	 *
	 * @return void
	 */
	public function init()
	{
		$this->addElement('text', 'comment', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [
				['stringLength', false, [1, 65535]],
				['callback', false, function($value){
					return !preg_match('/[<>]/', $value);
				}]
			]
		]);
	}
}
