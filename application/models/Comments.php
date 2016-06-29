<?php

class Application_Model_CommentsRow extends Zend_Db_Table_Row_Abstract
{
	/**
	 * Renders comment content.
	 *
	 * @param	integer $limit
	 *
	 * @return string
	 */
	public function renderContent($limit = false)
	{
		$output = '';

		for ($i = 0; $i < strlen($this->comment);)
		{
			if (preg_match('/^' . My_CommonUtils::$link_regex . '/', substr($this->comment, $i), $matches))
			{
				$output .= '<a href="' . htmlspecialchars(My_CommonUtils::renderLink($matches[0])) . '" target="_blank">' . $matches[0] . '</a>';
				$i += strlen($matches[0]);
			}
			else
			{
				$output .= preg_replace('/\n/', '<br>', $this->comment[$i++]);
			}

			if ($limit && $i > $limit)
			{
				$output = trim($output) . '... <a href="#" class="moreButton">See more...</a>';
				break;
			}
		}

		return $output;
	}
}

class Application_Model_Comments extends Zend_Db_Table_Abstract
{
	/**
	 * @var	Application_Model_Comments
	 */
	protected static $_instance;

    /**
     * The table name.
     *
     * @var string
     */
 	protected $_name = 'comments';

    /**
     * Classname for row.
     *
     * @var string
     */
	protected $_rowClass = 'Application_Model_CommentsRow';

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'User' => [
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'user_id'
        ],
		'News' => [
			'columns' => 'news_id',
			'refTableClass' => 'Application_Model_News',
			'refColumns' => 'id'
        ]
    );

	public static function getInstance() {

		if (null === self::$_instance) {

			self::$_instance = new self();

		}

		return self::$_instance;

	}

	/**
     * Returns an instance of a Zend_Db_Table_Select object.
     *
     * @param bool $withFromPart Whether or not to include the from part of the select based on the table
     * @return Zend_Db_Table_Select
     */
    public function publicSelect($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
        return parent::select($withFromPart)->where('comments.isdeleted =?', 0);
    }

	/**
	 * Returns comments count by news ID.
	 *
	 * @param	integer	$news_id
	 *
	 * @return	integer
	 */
	public function getCountByNewsId($news_id)
	{
		$result = $this->fetchRow(
			$this->select()
				->from('comments', array('count(*) as comment_count'))
				->where('news_id=?', $news_id)
				->where('isdeleted =?', 0)
		);

		if ($result)
		{
			return $result->comment_count;
		}

		return 0;
	}

	/**
	 * Finds records by news ID.
	 *
	 * @param	integer	$news_id
	 * @param	integer	$limit
	 * @param	integer	$limitstart
	 *
	 * @return	array
	 */
	public function findAllByNewsId($news_id, $limit, $limitstart = 0)
	{
		return $this->fetchAll(
			$this->select()
				->from($this, 'comments.*')
				->where('news_id=?', $news_id)
				->where('isdeleted =?', 0)
				->joinLeft('user_data', 'comments.user_id = user_data.id', '')
				->where('user_data.id IS NOT NULL')
				->where('user_data.status =?', 'active')
				->order('comments.id DESC')
				->group('comments.id')
				->limit($limit, $limitstart)
		);
	}

	public static function viewMoreLabel($count, $limit = 30)
	{
		$label = 'Show ';

		if ($count <= $limit)
		{
			$label .= $count . ' more';
		}
		else
		{
			$label .= 'previous';
		}

		$label .= ' comment';

		if ($count != 1)
		{
			$label .= 's';
		}

		return $label;
	}

	/**
	 * Checks if comment id valid.
	 *
	 * @param	integer	$comment_id
	 * @param	mixed	$comment
	 * @param	mixed	$deleted
	 *
	 * @return	boolean
	 */
	public static function checkId($comment_id, &$comment, $deleted = null)
	{
		if ($comment_id == null)
		{
			return false;
		}

		$comment = self::findById($comment_id, $deleted);

		return $comment != null;
	}

	/**
	 * Finds record by ID.
	 *
	 * @param	integer	$id
	 *
	 * return	mixed	If success Application_Model_CommentsRow, otherwise NULL
	 */
	public static function findById($id, $deleted = null)
	{
		$db = self::getInstance();

		$query = $db->select()->where('id =?', $id);

		if ($deleted !== null)
		{
			$query->where('isdeleted =?', $deleted);
		}

		$result = $db->fetchRow($query);

		return $result;
	}

	/**
	 * Saves form.
	 *
	 * @param	Application_Form_Comment $form
	 * @param	Application_Model_NewsRow $news
	 * @param	Application_Model_UserRow $user
	 * @return	Application_Model_CommentsRow
	 */
	public function save(Application_Form_Comment $form, Application_Model_NewsRow $news, Application_Model_UserRow $user)
	{
		$row = $this->createRow($form->getValues());
		$row->user_id = $user->id;
		$row->news_id = $news->id;
		$row->created_at = new Zend_Db_Expr('NOW()');
		$row->updated_at = new Zend_Db_Expr('NOW()');
		$row->save();

		$news->comment++;
		$news->save();

		return $row;
	}

	/**
	 * Deletes row.
	 *
	 * @param	Application_Model_CommentsRow $comment
	 * @param	Application_Model_NewsRow $news
	 * @return	boolean
	 */
	public function deleteRow(Application_Model_CommentsRow $comment, Application_Model_NewsRow $news)
	{
		$comment->isdeleted = 1;
		$comment->updated_at = new Zend_Db_Expr('NOW()');
		$comment->save();

		$news->comment--;
		$news->save();

		return true;
	}
}
