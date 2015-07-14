<?php

/**
 * Comment user notify table model class.
 */
class Application_Model_CommentUserNotify extends Zend_Db_Table_Abstract
{
    /**
     * @var string
     */
 	protected $_name = 'comment_user_notify';

	/**
	 * @var	array
	 */
	protected $_referenceMap = array(
		'Comment' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_Comments',
			'refColumns' => 'comment_id'
        ),
		'User' => array(
			'columns' => 'id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'user_id'
        )
    );
}
