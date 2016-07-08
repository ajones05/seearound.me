<?php
/**
 * Setting form class.
 */
class Admin_Form_Setting extends Zend_Form
{
  /**
   * Initialize form.
   *
   * @return void
   */
  public function init()
  {
    $this->addElement('text', 'name', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [
				['stringLength', false, [1, 255]]
			]
		]);

    $this->addElement('text', 'value', [
			'filters' => ['StringTrim']
		]);

    $this->addElement('text', 'description', [
			'filters' => ['StringTrim'],
			'validators' => [
				['stringLength', false, [0, 2000]]
			]
		]);
  }
}
