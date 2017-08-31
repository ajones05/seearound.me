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

		$this->addElement('multiselect', 'filter', [
			'required' => false,
			'multiOptions' => Application_Model_News::$filters
		]);

		$this->addElement('multiselect', 'category_id', [
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

	/**
	 * Returns search filter value.
	 *
	 * @param mixed $filter
	 * @return array
	 */
	public static function getFilter($filter)
	{
		if (!is_array($filter))
		{
			$filter = trim($filter) === '' ? [] : (array) $filter;
		}
		else
		{
			if ($filter == [''])
			{
				$filter = [];
			}
		}

		if ($filter == null)
		{
			$filter[] = 4;
		}

		return $filter;
	}
}
