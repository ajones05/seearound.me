<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{ 
	protected function _initConfig() { 
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
            Zend_Registry::set('config_global', $config);
            Zend_Registry::set('env', APPLICATION_ENV);
	}
	
	protected function _initAutoload() { 
            $loader = Zend_Loader_Autoloader::getInstance();
            $loader->registerNamespace('My_');
	}

	protected function _initDB() {       
            $dbConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/db.ini');
            $dbAdapter = Zend_Db::factory($dbConfig->adapter, array(
	        'host'     => $dbConfig->hostname,
		'username' => $dbConfig->username,
		'password' => $dbConfig->password,
		'dbname'   => $dbConfig->dbname
	    ));
		
	    My_Db_Table_Abstract::setDefaultAdapter($dbAdapter);
	    Zend_Registry::set('db', $dbAdapter); 
	    if (APPLICATION_ENV == 'development') {
                $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
	    	$profiler->setEnabled(true);
		$dbAdapter->setProfiler($profiler);
	   }
	}


}
