<?php

class Application_Model_Voting extends Zend_Db_Table_Abstract
{
	/**
	 * @var	string
	 */
    protected $_name = 'votings';

	/**
	 * Saves voting data.
	 *
	 * @param	integer $vote
	 * @param	integer $user_id
	 * @param	Application_Model_NewsRow $news
	 * @return	Zend_Db_Table_Abstract
	 */
    public function saveVotingData($vote, $user_id, Application_Model_NewsRow &$news)
	{
		$row = $this->createRow(array(
			'vote' => $vote,
			'user_id' => $user_id,
			'news_id' => $news->id
		));

		$row->save();

		$news->vote = max(0, $news->vote + $vote);
		$news->save();

		return $row;
    }

	/**
	 * Checks if news is liked by user.
	 *
	 * @param	integer	$news_id
	 * @param	integer	$user_id
	 *
	 * @return	string
	 */
	public function findNewsLikeByUserId($news_id, $user_id)
	{
        $result = $this->fetchRow(
			$this->select()
                ->where('user_id=?', $user_id)
                ->where('news_id=?', $news_id)
		);

		return $result;
	}
}
