<?php
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

/**
 * Address form class.
 */
class Application_Form_Address extends Zend_Form
{
	/**
	 * Contains parameters for ignore validation rules.
	 * @var array
	 */
	protected $ignore = [];

	/**
	 * Constructor
	 *
	 * Registers form view helper as decorator
	 *
	 * @param mixed $options
	 * @return void
	 */
	public function __construct($options = null)
	{
		if (isset($options['ignore']))
		{
			$this->ignore = $options['ignore'];
			unset($options['ignore']);
		}

		parent::__construct($options);
	}

	/**
	 * Initialize form (used by extending classes).
	 */
	public function init()
	{
		if (!$this->isIgnore('address'))
		{
			// TODO: refactoring
			$this->addElement('text', 'address');
			$this->addElement('text', 'latitude');
			$this->addElement('text', 'longitude');
			$this->addElement('text', 'street_name');
			$this->addElement('text', 'street_number');
			$this->addElement('text', 'city');
			$this->addElement('text', 'state');
			$this->addElement('text', 'country');
			$this->addElement('text', 'zip');
			$this->addElement('text', 'timezone');
		}
	}

	/**
	 * Validate the form
	 *
	 * @param  array $data
	 * @return boolean
	 */
	public function isValid($data)
	{
		$valid = parent::isValid($data);

		if ($this->isIgnore('address'))
		{
			return $valid;
		}

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
			v::optional(v::stringType())
				->assert(My_ArrayHelper::getProp($data, 'street_name'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Street Name: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::stringType())
				->assert(My_ArrayHelper::getProp($data, 'street_number'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Street Number: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::stringType())
				->assert(My_ArrayHelper::getProp($data, 'city'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('City: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional(v::stringType())
				->assert(My_ArrayHelper::getProp($data, 'state'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('State: ' .
				$exception->getMessage());
		}

		$country = My_ArrayHelper::getProp($data, 'country');

		try
		{
			v::optional(v::countryCode())->assert($country);
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$country = null;
			$this->addErrorMessage('Country: ' .
				$exception->getMessage());
		}

		try
		{
			v::optional($country ? v::postalCode($country) : v::stringType())
				->assert(My_ArrayHelper::getProp($data, 'zip'));
		}
		catch (ValidationException $exception)
		{
			$valid = false;
			$this->addErrorMessage('Zip: ' .
				$exception->getMessage());
		}

		if (!$valid)
		{
			$this->markAsError();
		}

		return $valid;
	}

	/**
	 * Checks if rule is ignored.
	 *
	 * @param string $rule The rule name
	 * @return boolean
	 */
	public function isIgnore($rule)
	{
		return in_array($rule, $this->ignore);
	}
}
