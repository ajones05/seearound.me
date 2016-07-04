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
		$config = isset($options['_config']) ? $options['_config'] :
			Zend_Registry::get('config_global');
		unset($options['_config']);

		$fb = new Facebook\Facebook([
			'app_id' => $config->facebook->app->id,
			'app_secret' => $config->facebook->app->secret,
			'default_graph_version' => $config->facebook->api->version
		]+$options);

		return $fb;
	}
}
