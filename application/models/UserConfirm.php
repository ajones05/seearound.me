<?php
/**
 * Row class for user confirm code.
 */
class Application_Model_UserConfirm extends Zend_Db_Table_Abstract
{
	/**
	 * @var	array
	 */
	public static $type = [
		'registration' => 0,
		'password' => 1
	];

    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'user_confirm';

	/**
	 * @var	array
	 */
    protected $_dependentTables = [
		'Application_Model_User'
	];

	/**
	 * @var	array
	 */
    protected $_referenceMap = [
		'User' => [
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		]
    ];

	/**
	 * Generates unique confirm code.
	 *
	 * @return string
	 */
	public function generateConfirmCode()
	{
		do
		{
			$code = My_CommonUtils::generateKey(12);
		}
		while ($this->findByCode($code) != null);

		return $code;
	}

	/**
	 * Finds record by code.
	 *
	 * @param	string $code
	 * return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByCode($code)
	{
		return $this->fetchRow($this->select()->where('code=?', $code));
	}

	/**
	 * Saves form.
	 *
	 * @param	array $data
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function save(array $data)
	{
		$row = $this->createRow($data);
		$row->deleted = 0;
		$row->created_at =
		$row->created_at = new Zend_Db_Expr('NOW()');
		$row->code = $this->generateConfirmCode();
		$row->save();
		return $row;
	}

	/**
	 * Deletes user codes by user id and type.
	 *
	 * @param	Zend_Db_Table_Row_Abstract $user
	 * @param	integer $type
	 * @return	integer
	 */
	public function deleteUserCode($user, $type)
	{
		return $this->update([
			'deleted' => 1,
			'updated_at' => new Zend_Db_Expr('NOW()')
		], [
			'user_id=?' => $user->id,
			'type_id=?' => $type,
			'deleted=?' => 0
		]);
	}
}
