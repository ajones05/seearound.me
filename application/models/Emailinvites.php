<?php

class Application_Model_Emailinvites extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'email_invites';

	/**
	 * Finds record by code.
	 *
	 * @param string $code
	 * @return mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByCode($code)
	{
		return $this->fetchRow($this->select()->where('code=?', $code));
	}

	/**
	 * Finds record by email.
	 *
	 * @param string $email
	 * @return mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public function findByEmail($email)
	{
		return $this->fetchRow($this->select()->where('self_email=?', $email));
	}

	/**
	 * Returns invites count.
	 *
	 * @return integer
	 */
	public function getInvitesCount()
	{
		$result = $this->fetchRow(
			$this->select()
				->from($this, ['count(*) as count'])
				->where('status=?', 0)
		);

		return $result ? $result->count : 0;
	}
}
