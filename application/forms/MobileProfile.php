<?php

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
        $this->addElement(
			'text',
			'name',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 50))
				)
			)
		);

		// TODO: disable?

        $this->addElement(
			'text',
			'email',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('EmailAddress')
				)
			)
		);

		// TODO: min/max date

        $this->addElement(
			'text',
			'birth_date',
			array(
				'required' => false,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('Date', false, array('format' => 'dddd/MMMM/yyyy'))
				)
			)
		);

        $this->addElement(
			'checkbox',
			'public_profile',
			array('required' => false)
		);

        $this->addElement(
			'text',
			'activities',
			array(
				'required' => false,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(0, 250))
				)
			)
		);

        $this->addElement(
			'select',
			'gender',
			array(
				'required' => false,
				'multiOptions' => array(
					'Male' => 'Male',
					'Female' => 'Female'
				)
			)
		);
    }
	
    /**
     * Validate the form.
     *
     * @param 	array	$data
	 *
     * @return	boolean
     */
    public function isValid($data)
	{
		if (!parent::isValid($data))
		{
			return false;
		}

		$upload = new Zend_File_Transfer;

		if (count($upload->getFileInfo()))
		{
			$upload->setValidators(array(
				array('Extension', false, array('jpg', 'jpeg', 'png', 'gif')),
				array('MimeType', false, array('image/jpeg', 'image/png', 'image/gif')),
				array('Count', false, 1)
			));

			if (!$upload->isValid('image'))
			{
				return false;
			}

			$ext = My_File::$mimetype_extension[$upload->getMimeType('image')];

			do
			{
				$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
				$full_path = ROOT_PATH . '/www/upload/' . $name;
			}
			while (file_exists($full_path));

			$upload->addFilter('Rename', $full_path);
			$upload->receive();

			$thumb = new My_Thumbnail($full_path);

			$thumb->resize(320, 320);
			$thumb->save(ROOT_PATH . '/uploads/' . $name, 60);

			$this->addElement('text', 'image', array('value' => $name));
		}

		return true;
	}
}
