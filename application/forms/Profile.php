<?php
/**
 * Profile form class.
 */
class Application_Form_Profile extends Application_Form_Address
{
	/**
	 * Initialize form.
	 */
	public function init()
	{
		parent::init();

		$this->addElement('text', 'email', [
			'label' => 'Email Address',
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['EmailAddress']]
		]);

		$this->addElement('checkbox', 'public_profile', [
			'label' => 'Allow other users to see your Email address?',
			'required' => false
		]);

		$this->addElement('text', 'name', [
			'label' => 'Name',
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['stringLength', false, [1, 50]]]
		]);

		$this->addElement('select', 'gender', [
			'label' => 'Gender',
			'required' => false,
			'multiOptions' => Application_Model_User::$genderId
		]);

		$this->addElement('text', 'activities', [
				'label' => 'Interest',
				'required' => false,
				'filters' => ['StringTrim'],
				'validators' => [['stringLength', false, [0, 250]]]
		]);

		$days = array_map(function($item){
			return str_pad($item, 2, '0', STR_PAD_LEFT); 
		}, range(1, 31));

		$this->addElement('select', 'birth_day', [
			'required' => false,
			'multiOptions' => array_combine(['']+range(1, 31), ['Day']+$days)
		]);

		$months = array_map(function($item){
			return str_pad($item, 2, '0', STR_PAD_LEFT); 
		}, range(1, 12));

		$this->addElement('select', 'birth_month', [
			'required' => false,
			'multiOptions' => array_combine(['']+range(1, 12), ['Month']+$months)
		]);

		$years = range(date('Y'), 1905);

		$this->addElement('select', 'birth_year', [
			'required' => false,
			'multiOptions' => array_combine(['']+$years, ['Year']+$years)
		]);

		$this->addElement('select', 'timezone', [
			'label' => 'Time zone',
			'required' => false,
			'multiOptions' => [''=>'UTC']+My_CommonUtils::$timezone
		]);
	}
}
