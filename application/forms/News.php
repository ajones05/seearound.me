<?php

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
        $this->addElement(
			'text',
			'user_id',
			array('required' => true)
		);

        $this->addElement(
			'text',
			'news',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 500))
				)
			)
		);

        $this->addElement(
			'text',
			'address',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
			)
		);

        $this->addElement(
			'text',
			'latitude',
			array(
				'required' => true,
				'validators' => array(
					array('validator' => 'Float')
				)
			)
		);

        $this->addElement(
			'text',
			'longitude',
			array(
				'required' => true,
				'validators' => array(
					array('validator' => 'Float')
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

		if (strpos($data['news'], '<') > 0 || strpos($data['news'], '>') > 0)
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

			if (!$upload->isValid('images'))
			{
				return false;
			}

			$ext = My_File::$mimetype_extension[$upload->getMimeType('images')];

			do
			{
				$name = strtolower(My_StringHelper::generateKey(10)) . '.' . $ext;
				$full_path = ROOT_PATH . '/uploads/' . $name;
			}
			while (file_exists($full_path));

			$upload->addFilter('Rename', $full_path);
			$upload->receive();

			$thumb = new My_Thumbnail($full_path);

			$thumb->resize(960, 960);
			$thumb->save(ROOT_PATH . '/newsimages/' . $name, 60);

			$thumb->resize(320, 320);
			$thumb->save(ROOT_PATH . '/tbnewsimages/' . $name, 60);

			$this->addElement('text', 'images', array('value' => $name));
		}

		return true;
	}
}
