<?php
/**
 * Post search form class.
 */
class Application_Form_PostSearch extends Zend_Form
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
			'keywords',
			array(
				'required' => false,
				'placeholder' => 'Search Posts...',
				'decorators' => array('ViewHelper'),
				'value' => ''
			)
		);
        $this->addElement(
			'select',
			'filter',
			array(
				'required' => false,
				'decorators' => array('ViewHelper'),
				'value' => '',
				'style' => 'display:none',
				'multiOptions' => array(
					'' => 'View all posts',
					'0' => 'Mine Only',
					'1' => 'My Interests',
					'2' => 'Following'
				)
			)
		);
    }
}
