<?php
/**
 * Login status model class.
 */
class Application_Model_Loginstatus extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	protected $_name = 'login_status';

	/**
	 * Generates user login token.
	 *
	 * @return	string
	 */
	public function generateToken()
	{
		do
		{
			$token = My_StringHelper::generateKey(64);
		}
		while ($this->findByToken($token));

		return $token;
	}

	/**
	 * Finds record by acccess token.
	 *
	 * @param	string $token Access token
	 * @return	mixed	If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByToken($token)
	{
		return $this->fetchRow($this->select()->where('token=?', $token));
	}

	/**
	 * Saves login state.
	 *
	 * @param	Zend_Db_Table_Row_Abstract $user
	 * @return Zend_Db_Table_Row_Abstract
	 */
	public function save($user, $token=false)
	{
		$row = $this->createRow([
			'user_id' => $user->id,
			'login_time' => new Zend_Db_Expr('NOW()'),
			'visit_time' => new Zend_Db_Expr('NOW()'),
			'ip_address' => $_SERVER['REMOTE_ADDR']
		]);

		if ($token)
		{
			$row->token = $this->generateToken();
		}

		$row->save();

		return $row;
	}
}
