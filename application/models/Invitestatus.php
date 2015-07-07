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
    protected $_referenceMap = array(
		'User' => array(
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		)
    );
}
