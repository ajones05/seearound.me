<?php
use Respect\Validation\Validator as v;

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
		if ($this->isIgnore('address'))
		{
			return;
		}

		$this->addElement('text', 'address', [
			'required' => false,
			'filters' => ['StringTrim']
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

		$this->addElement('text', 'street_name', [
			'required' => false,
			'validators' => [
				['callback', false, v::stringType()]
			]
		]);

		$this->addElement('text', 'street_number', [
			'required' => false,
			'validators' => [
				['callback', false, v::stringType()]
			]
		]);

		$this->addElement('text', 'city', [
			'required' => false,
			'validators' => [
				['callback', false, v::stringType()]
			]
		]);

		$this->addElement('text', 'state', [
			'required' => false,
			'validators' => [
				['callback', false, v::stringType()]
			]
		]);

		$this->addElement('text', 'country', [
			'required' => false,
			'validators' => [
				['callback', false, v::countryCode()]
			]
		]);

		$this->addElement('text', 'zip', [
			'required' => false,
			'validators' => [
				['callback', false, function($value, $data){
					return !empty($data['country']) ?
						v::postalCode($data['country'])->validate($value) :
						v::stringType()->validate($value);
				}]
			]
		]);

		$this->addElement('text', 'timezone', [
			'required' => false,
			'filters' => ['StringTrim']
		]);
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
