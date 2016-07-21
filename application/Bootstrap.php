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

		if (Zend_Auth::getInstance()->hasIdentity())
		{
			$router->addRoute(
				'/',
				new Zend_Controller_Router_Route(
					'/',
					array(
						'controller' => 'post',
						'action' => 'list'
					)
				)
			);

			$router->addRoute(
				'center',
				new Zend_Controller_Router_Route(
					'center/:center',
					array(
						'controller' => 'post',
						'action' => 'list'
					)
				)
			);
		}
		else
		{
			$router->addRoute(
				'change-password',
				new Zend_Controller_Router_Route(
					'change-password/:code',
					['controller' => 'index', 'action' => 'change-password'],
					['code' => '[A-Z0-9]+']
				)
			);

			$router->addRoute(
				'change-password-success',
				new Zend_Controller_Router_Route(
					'change-password-success',
					['controller' => 'index', 'action' => 'change-password-success']
				)
			);

			$router->addRoute(
				'forgot',
				new Zend_Controller_Router_Route(
					'forgot',
					['controller' => 'index', 'action' => 'forgot']
				)
			);

			$router->addRoute(
				'forgot-success',
				new Zend_Controller_Router_Route(
					'forgot-success',
					['controller' => 'index', 'action' => 'forgot-success']
				)
			);
		}

		$router->addRoute(
			'post',
			new Zend_Controller_Router_Route(
				'post/:id',
				array(
					'controller' => 'post',
					'action' => 'view'
				),
				array('id' => '\d+')
			)
		);

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
					'action' => 'login',
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
					'action' => 'login'
				)
			)
		);

		$router->addRoute(
			'confirm',
			new Zend_Controller_Router_Route(
				'confirm/:code',
				['controller' => 'home', 'action' => 'reg-confirm'],
				['code' => '[A-Z0-9]+']
			)
		);
	}

	protected function _initLog()
	{
		if (PHP_SAPI == 'cli')
		{
			return null;
		}

		$options = $this->getOption('resources');
		$logger = Zend_Log::factory($options['log']);
		$logger->addPriority('exception', 8);
		Zend_Registry::set('logger', $logger);

		// TODO: refactoring
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
		$user = Application_Model_User::getAuth();

		if ($user == null)
		{
			return false;
		}

		$auth = Zend_Auth::getInstance()->getIdentity();
		$status = (new Application_Model_Loginstatus)->find($auth['login_id'])->current();

		if (!$status)
		{
			return false;
		}

		$status->visit_time = new Zend_Db_Expr('NOW()');
		$status->save();
    }

    /**
     * Init update user data expiration.
     *
     * @return void
     */
    public function _initUserData()
    {
		if (!Zend_Auth::getInstance()->hasIdentity())
		{
			return true;
		}

		$userData = new Zend_Session_Namespace('userData');

		if ($userData->data)
		{
			$userData->setExpirationSeconds(3);
		}
    }
}
