<?php
/**
 * This is the model class for table "setting".
 */
class Admin_Model_Setting extends Zend_Db_Table_Abstract
{
	/**
   * The table name.
   *
   * @var string
   */
  protected $_name = 'setting';

	/**
	 * Finds record by ID.
	 *
	 * @param	string $name Name.
	 * @return	object Record found. Null if none is found.
	 */
	public function findById($id)
	{
		return $this->fetchRow($this->select()->where('id=?',$id));
	}
}
