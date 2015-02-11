<?php
/**
 * Base bootstrap class.
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initConfig()
	{
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
		Zend_Registry::set('config_global', $config);
		Zend_Registry::set('env', APPLICATION_ENV);
	}

	protected function _initAutoload()
	{
		$loader = Zend_Loader_Autoloader::getInstance();
		$loader->registerNamespace('My_');
	}

	protected function _initDB()
	{
		$config = Zend_Registry::get('config_global');
		$db = Zend_Db::factory($config->resources->db);
		Zend_Db_Table::setDefaultAdapter($db);
	}
}
