<?php 
/**
 * This is the model class for table "facebook_temp_users".
 */
class Application_Model_Fbtempusers extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'facebook_temp_users';

	/**
	 * Finds records by receiver network ID.
	 *
	 * @param string $network_id
	 * @param integer $sender_id
	 * return array
	 */	
	public static function findAllByNetworkId($network_id, $sender_id = null)
	{
		$model = new self;
		$query = $model->select()
			->where('reciever_nw_id=?', $network_id)
			->limit(100);

		if ($sender_id)
		{
			$query->where('sender_id=?', $sender_id);
		}

		return $model->fetchAll($query);
	}
}
