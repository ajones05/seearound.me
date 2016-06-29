<?php

class Application_Model_Voting extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
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
	 * Returns vote row by user ID and post ID.
	 *
	 * @param	integer $post
	 * @param	integer $user
	 * @return	mixed Zend_Db_Table_Abstract on success, otherwise NULL
	 */
	public function findVote($post_id, $user_id = null)
	{
		$query = $this->select()
			->where('canceled=0')
			->where('news_id=?', $post_id)
			->order('id DESC');

		if ($user_id)
		{
			$query->where('user_id=?', $user_id);
		}

		return $this->fetchRow($query);
	}

	/**
	 * Checks if user can vote post.
	 *
	 * @param	Application_Model_UserRow $user
	 * @param	Application_Model_NewsRow $post
	 * @return	boolean
	 */
	public function canVote($user, $post)
	{
		if (!$user)
		{
			return false;
		}

		return $user->is_admin ? true : $user->id != $post->user_id;
	}

	/**
	 * Finds record by ID.
	 *
	 * @param	integer $id
	 * return	mixed If success Zend_Db_Table_Row, otherwise NULL
	 */
	public function findById($id)
	{
		$model = new self;
		$result = $model->fetchRow(
			$model->select()->where('id=?', $id)
		);
		return $result;
	}

	/**
	 * Cancel vote.
	 *
	 * @param	Zend_Db_Table_Row $vote
	 * return	Zend_Db_Table_Row
	 */
	public function cancelVote($vote)
	{
		$vote->updated_at = new Zend_Db_Expr('NOW()');
		$vote->canceled = 1;
		$vote->save();
		return $vote;
	}
}
