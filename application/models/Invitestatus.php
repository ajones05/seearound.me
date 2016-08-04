<?php
/**
 * Invite status model class.
 */
class Application_Model_Invitestatus extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
	 protected $_name = 'invite_status';

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
	 * Finds record by user ID.
	 *
	 * @param	integer	$user_id
	 * return	mixed If success Zend_Db_Table_Row_Abstract, otherwise NULL
	 */
	public static function findByUserId($user_id)
	{
		$model = new self;
		return $model->fetchRow($model->select()->where('user_id=?', $user_id));
	}

	/**
	 * Updates user invite count.
	 *
	 * @param mixed $user
	 * @return boolean
	 */
	public static function updateCount($user)
	{
		if (date('N') != 1)
		{
			return false;
		}

		$userInvite = self::findByUserId($user['id']);

		if (floor((time() - strtotime($userInvite->updated)) / 86400) >= 7)
		{
			$loginStatusModel = new Application_Model_Loginstatus;
			$result = $loginStatusModel->fetchRow(
				$loginStatusModel->select()
					->from($loginStatusModel, 'count(*) as count')
					->where('user_id=?', $user['id'])
					->where('login_time>=DATE_SUB(NOW(),INTERVAL 7 DAY)')
			);

			if ($result && $result->count > 5)
			{
				(new self)->update([
					'updated' => new Zend_Db_Expr('NOW()'),
					'invite_count' => $userInvite->invite_count +
						floor($result->count / 5),
				], 'id=' . $userInvite->id);
			}
		}

		return true;
	}
}
