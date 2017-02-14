<?php
use Respect\Validation\Validator as v;

/**
 * Post search form class.
 */
class Application_Form_PostSearch extends Zend_Form
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

		$this->addElement('select', 'filter', [
			'required' => false,
			'filters' => ['StringTrim'],
			'decorators' => ['ViewHelper'],
			'value' => '',
			'style' => 'display:none',
			'multiOptions' => [
				'' => 'Most interesting',
				'3' => 'Most recent',
				'0' => 'My posts',
				'1' => 'My interests',
				'2' => 'Following'
			]
		]);

		$this->addElement('select', 'category_id', [
			'required' => false,
			'multiOptions' => Application_Model_News::$categories
		]);

		$this->addElement('text', 'latitude', [
			'required' => true,
			'validators' => [
				['callback', false, v::stringType()->lat()]
			]
		]);

		$this->addElement('text', 'longitude', [
			'required' => true,
			'validators' => [
				['callback', false, v::stringType()->lng()]
			]
		]);

		$this->addElement('text', 'radius', [
			'required' => false,
			'validators' => [
				['Float'],
				['Between', false, ['min' => 0.25, 'max' => 2]]
			]
		]);

		$this->addElement('text', 'start', [
			'required' => false,
			'validators' => [['Callback', false, v::intVal()->min(0)]]
		]);
	}
}
