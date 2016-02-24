<?php
/**
 * This is the model class for table "address".
 */
class Application_Model_Address extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'address';

	/**
	 * @var	array
	 */
    protected $_dependentTables = [
		'Application_Model_User',
		'Application_Model_News'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'User' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'address_id'
		],
		'News' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'address_id'
		]
	];

	/**
	 * Formats address string.
	 *
	 * @param	mixed $address
	 * @return	string
	 */
	public static function format($address, array $options=['street'=>true])
	{
		$output = '';

		if (!empty($options['street']))
		{
			$street = trim(My_ArrayHelper::getProp($address, 'street_name'));

			if ($street !== '')
			{
				$number = trim(My_ArrayHelper::getProp($address, 'street_number'));

				if ($number !== '')
				{
					$output .= $number . ' ';
				}

				$output .= $street;
			}
		}

		$city = trim(My_ArrayHelper::getProp($address, 'city'));

		if ($city !== '')
		{
			if ($output !== '')
			{
				$output .= ', ';
			}

			$output .= $city;
		}

		$state = trim(My_ArrayHelper::getProp($address, 'state'));

		if ($state !== '')
		{
			if ($output !== '')
			{
				$output .= ', ';
			}

			$output .= $state;
		}

		$zip = trim(My_ArrayHelper::getProp($address, 'zip'));

		if ($zip !== '')
		{
			if ($state !== '')
			{
				$output .= ' ';
			}
			elseif ($output !== '')
			{
				$output .= ', ';
			}

			$output .= $zip;
		}

		$country = trim(My_ArrayHelper::getProp($address, 'country'));

		if ($country !== '')
		{
			if ($output !== '')
			{
				$output .= ', ';
			}

			$output .= $country;
		}

		return $output;
	}
}
