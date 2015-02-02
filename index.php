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
  
//defined('BASE_PATH')
 //    || define('BASE_PATH',  $proto.'://'.$host.'/');

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


 $ip  = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
 if($ip=='127.0.0.1'){
   $ip =  '122.177.1.213';
 }


/*
 $url = "http://freegeoip.net/json/$ip";
 $ch  = curl_init();
         
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 $data = curl_exec($ch);
 curl_close($ch);

if ($data) {
    $location = json_decode($data);
    define("LATITUDE_VAR", $location->latitude);
    define("LONGITUDE_VAR", $location->longitude);
 }
 */
 
    $_API = '4baf0555ee6c21285c16d0d5a1b8ea9fee51c3a8f2ec6d9136fe0a20b6acb305';
    $_URL = "http://api.ipinfodb.com/v3/ip-city/?key=$_API&ip=$ip&format=json";
    
    $ch  = curl_init();
     curl_setopt($ch, CURLOPT_URL, $_URL);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
     $data = curl_exec($ch);
     curl_close($ch);
    
    if ($data) {
        $location = json_decode($data);
        define("LATITUDE_VAR", $location->latitude);
        define("LONGITUDE_VAR", $location->longitude);
     } else {
      $location = new stdClass();
      $location->latitude = 28.631807153637553;
      $location->longitude = 77.21967117409667;
      define("LATITUDE_VAR", $location->latitude);
      define("LONGITUDE_VAR", $location->latitude);  
    }  
      
$application->bootstrap()
            ->run();