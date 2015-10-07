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
		require_once ROOT_PATH . '/vendor/autoload.php';

		$loader = Zend_Loader_Autoloader::getInstance();
		$loader->registerNamespace('My_');

		class_alias('Validation\Rules\Lat', 'Respect\Validation\Rules\Lat');
		class_alias('Validation\Exceptions\LatException',
			'Respect\Validation\Exceptions\LatException');
		class_alias('Validation\Rules\Lng', 'Respect\Validation\Rules\Lng');
		class_alias('Validation\Exceptions\LngException',
			'Respect\Validation\Exceptions\LngException');
		class_alias('Validation\Rules\LatLng', 'Respect\Validation\Rules\LatLng');
		class_alias('Validation\Exceptions\LatLngException',
			'Respect\Validation\Exceptions\LatLngException');
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

		$router->addRoute(
			'login',
			new Zend_Controller_Router_Route(
				'login',
				array(
					'controller' => 'index',
					'action' => 'index',
					'isLogin' => true
				)
			)
		);

		$router->addRoute(
			'register',
			new Zend_Controller_Router_Route(
				'register',
				array(
					'controller' => 'index',
					'action' => 'index'
				)
			)
		);
	}

	protected function _initLog()
	{
		$log_path = ROOT_PATH . '/log';
		is_dir($log_path) || mkdir($log_path, 0700);
		$log = $log_path . '/bootstrap_log_' . date('Y-m-d') . '.log';
		$writer = new Zend_Log_Writer_Stream($log);
		chmod($log, 0777);
		$logger = new Zend_Log($writer);

		return $logger;
	}

    /**
     * Init save last visit timestamp
     * 
     * @return void
     */
    public function _initSaveLastVisitTimestamp()
    {
		$auth = Zend_Auth::getInstance()->getIdentity();

		if (!(new Application_Model_User)->checkId($auth['user_id'], $user))
		{
			return false;
		}

		$status = (new Application_Model_Loginstatus)->find($auth['login_id'])->current();

		if (!$status)
		{
			return false;
		}

		$status->visit_time = (new DateTime)->format(DateTime::W3C);
		$status->save();
    }
}
