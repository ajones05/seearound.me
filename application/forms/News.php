<?php
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

/**
 * News form class
 */
class Application_Form_News extends Zend_Form
{
    /**
     * Initialize form (used by extending classes)
     *
     * @return void
     */
    public function init()
    {
		$this->addElement('text', 'news');
    }

    /**
     * Validate the form.
     *
     * @param 	array $data
     * @return	boolean
     */
    public function isValid($data)
	{
		$valid = parent::isValid($data);

		try
		{
			v::stringType()->length(1, 500)->regex('/[^<>]/')
				->assert(My_ArrayHelper::getProp($data, 'news'));
		}
		catch (Exception $e)
		{
			$valid = false;
			$this->addErrorMessage($e->getMessage());
		}

		if ($valid)
		{
			$upload = new Zend_File_Transfer;

			if (count($upload->getFileInfo()))
			{
				$upload->setValidators([
					['Extension', false, ['jpg', 'jpeg', 'png', 'gif']],
					['MimeType', false, ['image/jpeg', 'image/png', 'image/gif']],
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

					$this->addElement('text', 'image', ['value' => $name]);
				}
				else
				{
					$valid = false;
					$this->addErrorMessage(implode(', ', $upload->getMessages()));
				}
			}
		}

		return $valid;
	}
}
