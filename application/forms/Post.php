<?php
use Respect\Validation\Validator as v;

/**
 * Post form class.
 */
class Application_Form_Post extends Application_Form_Address
{
	/**
	 * The post body max length.
	 * @var integer
	 */
	public static $bodyMaxLength = 4000000;

	/**
	 * Initialize form (used by extending classes).
	 */
	public function init()
	{
		parent::init();

		$this->addElement('text', 'body', [
			'required' => true,
			'validators' => [
				['StringLength', false, ['min' => 1, 'max' => self::$bodyMaxLength]],
				['Callback', false, function($value){
					return !preg_match('/[<>]/', $value);
				}]
			]
		]);

		$this->addElement('text', 'delete_image', [
			'required' => false,
			'validators' => [['Callback', false, v::intVal()->equals(1)]]
		]);

		$this->addElement('select', 'category_id', [
			'required' => false,
			'multiOptions' => Application_Model_News::$categories
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
				['Count', false, 2]
			]);

			if ($upload->isValid('image'))
			{
				$ext = My_CommonUtils::$mimetype_extension[$upload->getMimeType('image')];

				do
				{
					$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
					$full_path = ROOT_PATH_WEB . '/uploads/' . $name;
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
