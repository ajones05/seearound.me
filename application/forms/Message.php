<?php
/**
 * Message form class.
 */
class Application_Form_Message extends Zend_Form
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
			'subject',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 250))
				)
			)
		);

        $this->addElement(
			'text',
			'message',
			array(
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 65535))
				)
			)
		);
    }
}
