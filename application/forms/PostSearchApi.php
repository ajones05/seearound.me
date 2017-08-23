<?php
use Respect\Validation\Validator as v;

/**
 * Post search API form class.
 */
class Application_Form_PostSearchApi extends Zend_Form
{
	/**
	 * Initialize form.
	 */
	public function init()
	{
		$this->addElement('text', 'keywords', [
			'required' => false,
			'filters' => ['StringTrim'],
			'placeholder' => 'Search Posts...',
			'decorators' => ['ViewHelper'],
			'value' => ''
		]);

		$this->addElement('multiselect', 'filter', [
			'required' => false,
			'multiOptions' => Application_Model_News::$filters
		]);

		$this->addElement('multiselect', 'category_id', [
			'required' => false,
			'multiOptions' => Application_Model_News::$categories
		]);

		$this->addElement('text', 'start', [
			'required' => false,
			'validators' => [['Callback', false, v::intVal()->min(0)]]
		]);

		$this->addElement('text', 'ne', [
			'required' => true,
			'validators' => [
				['callback', false, v::stringType()->latlng()]
			]
		]);

		$this->addElement('text', 'sw', [
			'required' => true,
			'validators' => [
				['callback', false, v::stringType()->latlng()]
			]
		]);
	}
}
