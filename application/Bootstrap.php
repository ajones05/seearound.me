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
		$loader->registerNamespace('Skoch');
	}

	protected function _initDB()
	{
		$config = Zend_Registry::get('config_global');
		$db = Zend_Db::factory($config->resources->db);
		Zend_Db_Table::setDefaultAdapter($db);
	}

	protected function _initRoutes()
	{
		$router = Zend_Controller_Front::getInstance()->getRouter();

		$router->addRoute(
			'about',
			new Zend_Controller_Router_Route(
				'about',
				array(
					'controller' => 'page',
					'view' => 'about'
				)
			)
		);

		$router->addRoute(
			'privacy',
			new Zend_Controller_Router_Route(
				'privacy',
				array(
					'controller' => 'page',
					'view' => 'privacy'
				)
			)
		);

		$router->addRoute(
			'terms',
			new Zend_Controller_Router_Route(
				'terms',
				array(
					'controller' => 'page',
					'view' => 'terms'
				)
			)
		);
	}

	protected function _initLog()
	{
		$log_path = ROOT_PATH . '/log';
		is_dir($log_path) || mkdir($log_path, 0700);
		$writer = new Zend_Log_Writer_Stream($log_path . '/bootstrap_log_' . date('Y-m-d') . '.log');
		$logger = new Zend_Log($writer);

		return $logger;
	}
}
