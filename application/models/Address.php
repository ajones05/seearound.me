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
	 * Finds record by ID.
	 *
	 * @param integer $id
	 * return mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findById($id)
	{
		$model = new self;
		return $model->fetchRow($model->select()->where('id=?', $id));
	}

	/**
	 * Formats address string.
	 *
	 * @param	mixed $address
	 * @return	string
	 */
	public static function format($address, array $options=['street'=>true])
	{
		$output = '';
		$alias = My_arrayHelper::getProp($options, 'alias', '');

		if (!empty($options['street']))
		{
			$street = trim(My_ArrayHelper::getProp($address, $alias.'street_name'));

			if ($street !== '')
			{
				$number = trim(My_ArrayHelper::getProp($address, $alias.'street_number'));

				if ($number !== '')
				{
					$output .= $number . ' ';
				}

				$output .= $street;
			}
		}

		$city = trim(My_ArrayHelper::getProp($address, $alias.'city'));

		if ($city !== '')
		{
			if ($output !== '')
			{
				$output .= ', ';
			}

			$output .= $city;
		}

		$state = trim(My_ArrayHelper::getProp($address, $alias.'state'));

		if ($state !== '')
		{
			if ($output !== '')
			{
				$output .= ', ';
			}

			$output .= $state;
		}

		$zip = trim(My_ArrayHelper::getProp($address, $alias.'zip'));

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

		$country = trim(My_ArrayHelper::getProp($address, $alias.'country'));

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
