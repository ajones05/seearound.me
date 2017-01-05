<?php
/**
 * Comment subscription table model class.
 */
class Application_Model_CommentSubscription extends Zend_Db_Table_Abstract
{
	/**
	 * @var string
	 */
	protected $_name = 'comment_subscription';

	/**
	 * @var array
	 */
	protected $_referenceMap = [
		'User' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'user_id'
		],
		'Post' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'post_id'
		]
	];

	/**
	 * Returns row by attributes.
	 *
	 * @param array $attr
	 * @return stdClass|null
	 */
	public function findByAttributes(array $attr)
	{
		$query = $this->select();
		foreach ($attr as $field => $value)
		{
			if ($value === null)
			{
				$query->where($field . ' IS NULL');
			}
			else
			{
				$query->where($field . '=?', $value);
			}
		}
		return $this->fetchRow($query);
	}
}
