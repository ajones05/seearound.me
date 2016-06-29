<?php
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

/**
 * Post search form class.
 */
class Application_Form_PostSearch extends Zend_Form
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
			'keywords',
			array(
				'required' => false,
				'placeholder' => 'Search Posts...',
				'decorators' => array('ViewHelper'),
				'value' => ''
			)
		);
        $this->addElement(
			'select',
			'filter',
			array(
				'required' => false,
				'decorators' => array('ViewHelper'),
				'value' => '',
				'style' => 'display:none',
				'multiOptions' => array(
					'' => 'Most interesting',
					'3' => 'Most recent',
					'0' => 'My posts',
					'1' => 'My interests',
					'2' => 'Following'
				)
			)
		);
        $this->addElement('text', 'latitude');
        $this->addElement('text', 'longitude');
        $this->addElement('text', 'radius');
        $this->addElement('text', 'start');
    }

	public function validateSearch($data)
	{
		$valid = true;

		try
		{
			v::stringType()->lat()
				->assert(My_ArrayHelper::getProp($data, 'latitude'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Latitude: ' .
				$exception->getMessage());
		}

		try
		{
			v::stringType()->lng()
				->assert(My_ArrayHelper::getProp($data, 'longitude'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Longitude: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::floatVal()->between(0.25, 2))
				->assert(My_ArrayHelper::getProp($data, 'radius'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Radius: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::intVal())->min(0)
				->assert(My_ArrayHelper::getProp($data, 'start'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Start: ' .
				$exception->getMessage());
		}

		$keywords = My_ArrayHelper::getProp($data, 'keywords');

		try
		{
			v::optional(v::stringType())->assert($keywords);
			$this->getElement('keywords')->setValue($keywords);
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Keywords: ' .
				$exception->getMessage());
		}

		$filter = My_ArrayHelper::getProp($data, 'filter');
		$filterErrors = [];

		if (!$this->getElement('filter')->isValid($filter))
		{
			$filterErrors[] = implode("\n", $this->getElement('filter')->getMessages());
		}

		try
		{
			v::optional(v::intVal())->assert($filter);
		}
		catch (ValidationException $exception)
		{
			$filterErrors[] = $exception->getFullMessage();
		}

		if (count($filterErrors))
		{
			$valid = false;
			$this->addErrorMessage('Filter: ' .
				implode("\n", $this->getElement('filter')->getMessages()));
		}

		return $valid;
	}
}
