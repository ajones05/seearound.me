<?php
/**
 * Facebook helper class.
 */
class My_Facebook
{
	/**
	 * Returns facebook api client instance.
	 *
	 * @param	array $options
	 * @return	Zend_Db_Select
	 */
	public static function getInstance(array $options=[])
	{
		$settings = isset($options['_settings']) ? $options['_settings'] :
			(new Application_Model_Setting)->findValuesByName([
				'fb_apiVersion',
				'fb_appId',
				'fb_appSecret'
			]);

		unset($options['_settings']);

		$fb = new Facebook\Facebook([
			'app_id' => $settings['fb_appId'],
			'app_secret' => $settings['fb_appSecret'],
			'default_graph_version' => $settings['fb_apiVersion']
		]+$options);

		return $fb;
	}
}
