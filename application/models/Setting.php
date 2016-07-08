<?php
/**
 * This is the model class for table "setting".
 */
class Application_Model_Setting extends Zend_Db_Table_Abstract
{
	/**
   * The table name.
   *
   * @var string
   */
  protected $_name = 'setting';

	/**
	 * Finds record by name.
	 *
	 * @param	string $name Name.
	 * @return	object Record found. Null if none is found.
	 */
	public function findValueByName($name)
	{
		$row = $this->fetchRow($this->select()->where('name=?',$name));
		return $row ? $row->value : null;
	}

	/**
	 * Finds records by names.
	 *
	 * @param	string $names Names.
	 * @return	array
	 */
	public function findValuesByName(array $names=[])
	{
		$query = $this->select();

		foreach ($names as  $name)
		{
			$query->orWhere('name=?', $name);
		}

		$result = [];
		$rows = $this->fetchAll($query);

		if ($rows != null)
		{
			foreach ($rows as $row)
			{
				$result[$row->name] = $row->value;
			}
		}

		return $result;
	}
}
