<?php

/**
 * Profile form class.
 */
class Application_Form_Profile extends Zend_Form
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
			'email',
			array(
				'label' => 'Email Address',
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('EmailAddress')
				)
			)
		);

        $this->addElement(
			'checkbox',
			'public_profile',
			array(
				'label' => 'Allow other users to see your Email address?',
				'required' => false
			)
		);

        $this->addElement(
			'text',
			'name',
			array(
				'label' => 'Name',
				'required' => true,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(1, 50))
				)
			)
		);

        $this->addElement(
			'select',
			'gender',
			array(
				'label' => 'Gender',
				'required' => false,
				'multiOptions' => array(
					'Male' => 'Male',
					'Female' => 'Female'
				)
			)
		);

        $this->addElement(
			'text',
			'activities',
			array(
				'label' => 'Interest',
				'required' => false,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(0, 250))
				)
			)
		);

        $this->addElement(
			'text',
			'address',
			array(
				'label' => 'Location',
				'required' => false,
				'filters' => array('StringTrim'),
				'validators' => array(
					array('stringLength', false, array(0, 255))
				)
			)
		);

        $this->addElement(
			'hidden',
			'latitude',
			array(
				'required' => true,
				'validators' => array(
					array('Float'),
					array(
						'name' => 'Between',
						false,
						array(
							'min' => -90,
							'max' => 90,
						)
					)
				)
			)
		);

        $this->addElement(
			'hidden',
			'longitude',
			array(
				'required' => true,
				'validators' => array(
					array('Float'),
					array(
						'name' => 'Between',
						false,
						array(
							'min' => -180,
							'max' => 180,
						)
					)
				)
			)
		);

		$days = array_map(function($item){
			return str_pad($item, 2, '0', STR_PAD_LEFT); 
		}, range(1, 31));

        $this->addElement(
			'select',
			'birth_day',
			array(
				'required' => false,
				'multiOptions' => array_combine(array_merge(array(''), range(1, 31)), array_merge(array('Day'), $days))
			)
		);

		$months = array_map(function($item){
			return str_pad($item, 2, '0', STR_PAD_LEFT); 
		}, range(1, 12));

        $this->addElement(
			'select',
			'birth_month',
			array(
				'required' => false,
				'multiOptions' => array_combine(array_merge(array(''), range(1, 12)), array_merge(array('Month'), $months))
			)
		);

		$years = range(date('Y'), 1905);

        $this->addElement(
			'select',
			'birth_year',
			array(
				'required' => false,
				'multiOptions' => array_combine(array_merge(array(''), $years), array_merge(array('Year'), $years))
			)
		);
    }
}
