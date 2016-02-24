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
		'confirm_email' => 0,
		'forgot_password' => 1
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
	 * Saves form.
	 *
	 * @param	array $data
	 * @return	Zend_Db_Table_Row_Abstract
	 */
	public function save(array $data)
	{
		$confirm = $this->createRow($data);
		$confirm->deleted = 0;
		$confirm->created_at = new Zend_Db_Expr('NOW()');

		do
		{
			$confirm->code = My_CommonUtils::generateKey(12);
		}
		while ($this->findByCode($confirm->code) != null);

		$this->updateDelete($confirm);

		$confirm->save();

		return $confirm;
	}

	/**
	 * Updates rows deleted status.
	 *
	 * @param	Zend_Db_Table_Row_Abstract $confirm
	 * @return	integer
	 */
	public function updateDelete($confirm)
	{
		return $this->update(
			['deleted' => 1, 'updated_at' => new Zend_Db_Expr('NOW()')],
			'(user_id=' . $confirm->user_id . ' AND type_id=' .
				$confirm->type_id . ' AND deleted=0)'
		);
	}

	/**
	 * Finds record by code.
	 *
	 * @param	string $code
	 * @param	boolean $deleted
	 * return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByCode($code, $deleted = null)
	{
		$query = $this->select()->where('code=?', $code);

		if ($deleted === false)
		{
			$query->where('deleted=0');
		}

		return $this->fetchRow($query);
	}
}
