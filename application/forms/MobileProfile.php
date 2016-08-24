<?php
use Respect\Validation\Validator as v;

/**
 * Mobile profile form class.
 */
class Application_Form_MobileProfile extends Zend_Form
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
			'validators' => [['stringLength', false, [1, 50]]]
		]);

		$this->addElement('text', 'email', [
			'required' => true,
			'filters' => ['StringTrim'],
			'validators' => [['EmailAddress']]
		]);

		$this->addElement('text', 'birth_date', [
			'required' => false,
			'filters' => ['StringTrim'],
			'validators' => [
				['Callback', false, v::date('d-m-Y')
					->between('01-01-1905', date('d-m-Y'))]
			]
		]);

		$this->addElement('checkbox', 'public_profile', [
			'required' => false
		]);

		$this->addElement('text', 'interest', [
			'required' => false,
			'filters' => ['StringTrim'],
			'validators' => [
				['stringLength', false, [0, 250]]
			]
		]);

		$this->addElement('select', 'gender', [
			'required' => false,
			'multiOptions' => [
				'Male' => 'Male',
				'Female' => 'Female'
			]
		]);
	}

	/**
	 * Validate the form.
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function isValid($data)
	{
		$isValid = parent::isValid($data);

		if (!$isValid)
		{
			return false;
		}

		$upload = new Zend_File_Transfer;

		if ($upload->getFileInfo() != null)
		{
			$this->addElement('text', 'image');

			$upload->setValidators([
				['Extension', false, ['jpg', 'jpeg', 'png', 'gif']],
				['MimeType', false, ['image/jpeg', 'image/png', 'image/gif'],
					['magicFile' => false]],
				['Count', false, 1]
			]);

			if (!$upload->isValid('image'))
			{
				$ext = My_CommonUtils::$mimetype_extension[$upload->getMimeType('image')];

				do
				{
					$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
					$full_path = ROOT_PATH_WEB . '/www/upload/' . $name;
				}
				while (file_exists($full_path));

				$upload->addFilter('Rename', $full_path);
				$upload->receive();

				$this->image->setValue($name);
			}
			else
			{
				$isValid = false;
				$this->image->addErrorMessage(implode(', ', $upload->getMessages()));
				$this->image->markAsError();
			}
		}

		return $isValid;
	}
}
