<?php
/**
 * Registration form class.
 */
class Application_Form_Registration extends Application_Form_Address
{
	/**
	 * Initialize form (used by extending classes).
	 */
	public function init()
	{
		parent::init();

		$this->addElement('text', 'name', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['stringLength', false, [1, 50]]]
		]);

		$this->addElement('text', 'email', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [
				['EmailAddress', true, ['domain' => false]],
				['Db_NoRecordExists', false, [
					'table' => 'user_data',
					'field' => 'Email_id'
				]]
			]
		]);

		$this->addElement('password', 'password', [
			'required' => true,
			'validators' => Application_Form_UserPassword::passwordValidators()
		]);
	}
}
