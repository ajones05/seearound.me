<?php

class Application_Model_UserProfile extends Zend_Db_Table_Abstract
{
    protected $_name = 'user_profile';

    protected $_referenceMap = array(
		'User' => array(
			'columns' => 'user_id',
			'refTableClass' => 'Application_Model_User',
			'refColumns' => 'id'
		)
    );
}
