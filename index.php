<?php
// Define path to root directory
defined('ROOT_PATH') 
    || define('ROOT_PATH', dirname(__FILE__));

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Define www directory path
$proto = (empty($_SERVER['HTTPS'])) ? 'http' : 'https';$host  = $_SERVER['HTTP_HOST'];$folder = basename(dirname(__FILE__));
defined('BASE_PATH')
	|| define('BASE_PATH',  $proto.'://'.$host.'/');

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()
            ->run();
