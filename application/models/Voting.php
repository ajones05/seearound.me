<?php
/**
 * This is the model class for table "votings".
 */
class Application_Model_Voting extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'votings';

	/**
	 * @var	array
	 */
	protected $_dependentTables = [
		'Application_Model_News'
	];

	/**
	 * @var	array
	 */
	protected $_referenceMap = [
		'News' => [
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
		]
	];

	/**
	 * Finds record by ID.
	 *
	 * @param integer $id
	 * @param array $options
	 * return mixed If success Zend_Db_Table_Row, otherwise NULL
	 */
	public function findById($id, $options)
	{
		return $this->fetchRow($this->select()->setIntegrityCheck(false)
			->from(['v' => 'votings'])
			->where('v.id=?', $id)
			->join(['p' => 'news'], 'p.id=v.news_id',
				My_ArrayHelper::getProp($options, 'post', ''))
			->where('p.isdeleted=0')
		);
	}

	/**
	 * Returns vote row by user ID and post ID.
	 *
	 * @param	integer $post
	 * @param	integer $user
	 * @return	mixed Zend_Db_Table_Abstract on success, otherwise NULL
	 */
	public function findVote($post_id, $user_id)
	{
		return $this->fetchRow(
			$this->select()
				->where('active=1')
				->where('news_id=?', $post_id)
				->where('user_id=?', $user_id)
				->order('id DESC')
		);
	}

	/**
	 * Checks if user can vote post.
	 *
	 * @param mixed $user
	 * @param mixed $post
	 * @return boolean
	 */
	public static function canVote($user, $post)
	{
		return $user != null && (!empty($user['is_admin']) ||
			$user['id'] != $post['user_id']) ? true : false;
	}
}
