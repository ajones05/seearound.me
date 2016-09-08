<?php
/**
 * This is the model class for table "setting".
 */
class Application_Model_Setting extends Zend_Db_Table_Abstract
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	 protected $_name = 'setting';

	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected static $_instance;

	/**
	 * Returns settings list instance.
	 *
	 * @return array
	 */
	public static function getInstance()
	{
		if (self::$_instance === null)
		{
			$enableCache = Zend_Registry::isRegistered('cache');
			$cache = $enableCache ? Zend_Registry::get('cache') : null;
			$settings = $enableCache ? $cache->load('settings') : null;

			if ($settings == null)
			{
				$model = new self;
				$result = $model->fetchAll($model->select()
					->from('setting', ['name', 'value']));

				$settings = [];

				foreach ($result as $setting)
				{
					$settings[$setting->name] = $setting->value;
				}

				if ($enableCache)
				{
					$cache->save($settings, 'settings');
				}
			}

			self::$_instance = $settings;
		}

		return self::$_instance;
	}
}
