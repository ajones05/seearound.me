<?php

/**
 * Comment form class.
 */
class Application_Form_Comment extends Zend_Form
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
			'comment',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 65535))
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

		if (strpos($data['comment'], '<') >= 0 || strpos($data['comment'], '>') >= 0)
		{
			return false;
		}

		return true;
	}
}
