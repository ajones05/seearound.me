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
					'' => 'View all posts',
					'0' => 'Mine Only',
					'1' => 'My Interests',
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
			v::stringType()->lat()->assert($data['latitude']);
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Latitude: ' .
				$exception->getMessage());
		}

		try
		{
			v::stringType()->lng()->assert($data['longitude']);
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Longitude: ' .
				$exception->getMessage());
		}

		try
		{
			v::floatVal()->between(0.5, 1.5)->assert($data['radius']);
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Radius: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::intVal())->min(0)->assert(
				My_ArrayHelper::getProp($data, 'start'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Start: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::stringType())->assert(
				My_ArrayHelper::getProp($data, 'keywords'));
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
